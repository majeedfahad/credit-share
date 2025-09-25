<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Card;
use \Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncomingPaymentController extends Controller
{
    public function store(Request $request)
    {
        $device = $request->attributes->get('device');
        $payload = $request->validate([
            'raw_text' => 'required|string',
            'received_at' => 'nullable|date',
        ]);
        $raw = $payload['raw_text'];
        $parsed = $this->parseMessage($raw);

        // card lookup (by last4 preferred)
        $card = null;
        if (!empty($parsed['card_last4'])) {
            $card = Card::where('last4', $parsed['card_last4'])->first();
        }
        if (!$card && !empty($parsed['card_type'])) {
            $card = Card::where('type', 'like', '%' . $parsed['card_type'] . '%')->first();
        }
        if (!$card) {
            return response()->json(['message' => 'Card not found. Create the card first or include last4 in message.'], 422);
        }

        if (!isset($parsed['amount'])) {
            return response()->json(['message' => 'Amount not detected in message'], 422);
        }

        $amount = $parsed['amount'];
        $receivedAt = $payload['received_at'] ?? ($parsed['datetime'] ?? now());

        DB::beginTransaction();
        try {
            // decide new balance: if message contains balance use it, else subtract
            if (isset($parsed['balance_after'])) {
                $newBalance = $parsed['balance_after'];
            } else {
                // use bcsub for safe decimals
                $newBalance = bcsub((string)$card->current_balance, (string)$amount, 2);
            }

            $payment = Payment::create([
                'card_id' => $card->id,
                'device_id' => $device->id,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'card_last4' => $card->last4,
                'card_type' => $parsed['card_type'] ?? null,
                'merchant' => $parsed['merchant'] ?? null,
                'description' => $parsed['description'] ?? null,
                'raw_text' => $raw,
                'received_at' => $receivedAt,
            ]);


            $card->current_balance = $newBalance;
            $card->save();

            DB::commit();
            return response()->json(['ok' => true, 'payment_id' => $payment->id], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('IncomingPayment error: ' . $e->getMessage(), ['raw' => $raw]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    private function parseMessage(string $text): array
    {
        $res = [
            'amount' => null,
            'balance_after' => null,
            'card_last4' => null,
            'card_type' => null,
            'merchant' => null,
            'description' => null,
            'datetime' => null,
        ];

        // نقسم السطور
        $lines = preg_split('/\r\n|\r|\n/', trim($text));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if (mb_strpos($line, 'بطاقة:') === 0) {
                if (preg_match('/بطاقة:(\d+)\s*;([^$]+)/u', $line, $m)) {
                    $res['card_last4'] = substr(trim($m[1]), -4);
                    $res['card_type'] = trim($m[2]);
                }
            } elseif (mb_strpos($line, 'مبلغ:') === 0) {
                if (preg_match('/مبلغ:\s*(?:SAR\s*)?([\d\.,]+)/u', $line, $m)) {
                    $res['amount'] = (float)str_replace(',', '', $m[1]);
                }
            } elseif (mb_strpos($line, 'لدى:') === 0) {
                $res['merchant'] = trim(str_replace('لدى:', '', $line));
            } elseif (mb_strpos($line, 'رصيد:') === 0) {
                if (preg_match('/رصيد:\s*(?:SAR\s*)?([\d\.,]+)/u', $line, $m)) {
                    $res['balance_after'] = (float)str_replace(',', '', $m[1]);
                }
            } elseif (mb_strpos($line, 'في:') === 0) {
                $dateStr = trim(str_replace('في:', '', $line));
                try {
                    $res['datetime'] = \Carbon\Carbon::parse($dateStr);
                } catch (\Exception $e) {}
            } else {
                // السطر الأول عادة وصف العملية
                if (!$res['description']) {
                    $res['description'] = $line;
                }
            }
        }

        return $res;
    }


    private function normalizeNumber(string $num): float
    {
        $arabic = ['۰', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٠', '٫', '،'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '.', '.'];
        $num = str_replace($arabic, $latin, $num);
        $num = str_replace(',', '', $num);
        $num = preg_replace('/[^\d\.]/', '', $num);
        return (float)$num;
    }

    private function tryParseDate(string $raw): Carbon|null
    {
        $raw = str_replace('/', '-', $raw);
        if (preg_match('/^([0-3]?\d)-([0-1]?\d)-([0-9]{2})\s*([0-2]?\d:[0-5]\d)?$/', $raw, $m)) {
            $day = $m[1];
            $mon = $m[2];
            $yr = 2000 + intval($m[3]);
            $time = trim($m[4] ?? '00:00');
            try {
                return Carbon::parse("$yr-$mon-$day $time");
            } catch (\Exception $e) {
                return null;
            }
        }
        try {
            return Carbon::parse($raw);
        } catch (\Exception $e) {
            return null;
        }
    }
}
