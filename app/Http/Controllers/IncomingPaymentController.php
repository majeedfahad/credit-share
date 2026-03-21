<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Card;
use App\Models\FailedPayment;
use App\Models\SalaryCycle;
use App\Models\MerchantCategory;
use App\Services\TelegramService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IncomingPaymentController extends Controller
{
    public function store(Request $request)
    {
        $device = $request->attributes->get("device");
        $payload = $request->validate([
            "raw_text" => "required|string",
            "received_at" => "nullable|date",
        ]);
        $raw = $payload["raw_text"];
        $parsed = $this->parseMessage($raw);

        // Card lookup
        $card = null;
        if (!empty($parsed["card_last4"])) {
            $card = Card::where("last4", $parsed["card_last4"])->first();
        }
        if (!$card && !empty($parsed["card_type"])) {
            $card = Card::where("type", "like", "%" . $parsed["card_type"] . "%")->first();
        }
        if (!$card) {
            $this->saveFailedPayment($device?->id, $raw, "Card not found");
            return response()->json(["message" => "Card not found"], 422);
        }

        if (!isset($parsed["amount"])) {
            $this->saveFailedPayment($device?->id, $raw, "Amount not detected");
            return response()->json(["message" => "Amount not detected"], 422);
        }

        $amount = $parsed["amount"];
        $receivedAt = $payload["received_at"] ?? ($parsed["datetime"] ?? now());

        DB::beginTransaction();
        try {
            if (isset($parsed["balance_after"])) {
                $newBalance = $parsed["balance_after"];
            } else {
                $newBalance = bcsub((string)$card->current_balance, (string)$amount, 2);
            }

            // Get or create salary cycle
            $salaryCycle = SalaryCycle::findOrCreateForDate(Carbon::parse($receivedAt));
            
            // Auto-classify
            $category = MerchantCategory::findCategoryForMerchant($parsed["merchant"] ?? null);

            $payment = Payment::create([
                "card_id" => $card->id,
                "device_id" => $device->id,
                "amount" => $amount,
                "balance_after" => $newBalance,
                "card_last4" => $card->last4,
                "card_type" => $parsed["card_type"] ?? null,
                "merchant" => $parsed["merchant"] ?? null,
                "description" => $parsed["description"] ?? null,
                "raw_text" => $raw,
                "received_at" => $receivedAt,
                "category_id" => $category?->id,
                "salary_cycle_id" => $salaryCycle->id,
            ]);

            $card->current_balance = $newBalance;
            $card->save();

            // Sync parent card balance when paying with sub-card
            if ($card->parent_card_id) {
                $parentCard = Card::find($card->parent_card_id);
                if ($parentCard) {
                    $parentCard->current_balance = bcsub((string)$parentCard->current_balance, (string)$amount, 2);
                    $parentCard->save();
                }
            }

            DB::commit();

            // Large transaction alert
            $threshold = (float) env('LARGE_PAYMENT_THRESHOLD', 500);
            if ($amount >= $threshold) {
                try {
                    $telegram = new TelegramService();
                    $merchant = $parsed["merchant"] ?? 'غير معروف';
                    $last4 = $card->last4;
                    $balance = $newBalance;
                    $telegram->sendMessage(
                        "💰 عملية كبيرة!\n{$amount} ريال لدى {$merchant}\nالبطاقة: *{$last4}\nالرصيد: {$balance}"
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Large transaction alert failed: ' . $e->getMessage());
                }
            }

            // Unclassified payment interactive classification
            if ($payment->category_id === null) {
                try {
                    $telegram = $telegram ?? new TelegramService();
                    $categories = \App\Models\Category::orderBy('sort_order')->get();
                    $buttons = [];
                    foreach ($categories as $cat) {
                        $buttons[] = [
                            'text' => ($cat->icon ?? '📁') . ' ' . $cat->name_ar,
                            'callback_data' => "classify:{$payment->id}:{$cat->id}",
                        ];
                    }
                    $merchant = $parsed["merchant"] ?? 'غير معروف';
                    $telegram->sendMessageWithButtons(
                        "❓ عملية غير مصنفة\n{$amount} ريال لدى {$merchant}\nاختر التصنيف:",
                        $buttons
                    );
                } catch (\Throwable $e) {
                    \Log::warning('Unclassified payment alert failed: ' . $e->getMessage());
                }
            }

            return response()->json([
                "ok" => true, 
                "payment_id" => $payment->id,
                "category" => $category?->name_ar,
                "cycle_spent" => $salaryCycle->total_spent,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error("IncomingPayment error: " . $e->getMessage(), ["raw" => $raw]);
            $this->saveFailedPayment($device?->id, $raw, $e->getMessage());
            return response()->json(["message" => "Server error"], 500);
        }
    }

    public function retry(Request $request)
    {
        $device = $request->attributes->get("device");
        $failed = FailedPayment::where("is_processed", false)
            ->where("retry_count", "<", 5)
            ->orderBy("created_at")
            ->limit(10)
            ->get();

        $results = [];
        foreach ($failed as $item) {
            $item->increment("retry_count");
            $item->last_retry_at = now();
            $item->save();

            $fakeRequest = new Request(["raw_text" => $item->raw_text]);
            $fakeRequest->attributes->set("device", $device);
            
            $response = $this->store($fakeRequest);
            
            if ($response->getStatusCode() === 201) {
                $item->is_processed = true;
                $item->save();
                $results[] = ["id" => $item->id, "status" => "success"];
            } else {
                $results[] = ["id" => $item->id, "status" => "failed"];
            }
        }

        return response()->json(["processed" => count($results), "results" => $results]);
    }

    private function saveFailedPayment(?int $deviceId, string $rawText, string $error): void
    {
        FailedPayment::create([
            "device_id" => $deviceId,
            "raw_text" => $rawText,
            "error_message" => $error,
        ]);
    }

    private function parseMessage(string $text): array
    {
        $res = [
            "amount" => null,
            "balance_after" => null,
            "card_last4" => null,
            "card_type" => null,
            "merchant" => null,
            "description" => null,
            "datetime" => null,
        ];

        $lines = preg_split("/\r\n|\r|\n/", trim($text));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === "") continue;

            if (mb_strpos($line, "بطاقة:") === 0) {
                if (preg_match("/بطاقة:(\d+)\s*;([^$]+)/u", $line, $m)) {
                    $res["card_last4"] = substr(trim($m[1]), -4);
                    $res["card_type"] = trim($m[2]);
                }
            } elseif (mb_strpos($line, "مبلغ:") === 0) {
                if (preg_match("/مبلغ:\s*(?:SAR\s*)?([\d\.,]+)/u", $line, $m)) {
                    $res["amount"] = (float)str_replace(",", "", $m[1]);
                }
            } elseif (mb_strpos($line, "لدى:") === 0) {
                $res["merchant"] = trim(str_replace("لدى:", "", $line));
            } elseif (mb_strpos($line, "رصيد:") === 0) {
                if (preg_match("/رصيد:\s*(?:SAR\s*)?([\d\.,]+)/u", $line, $m)) {
                    $res["balance_after"] = (float)str_replace(",", "", $m[1]);
                }
            } elseif (mb_strpos($line, "في:") === 0) {
                $dateStr = trim(str_replace("في:", "", $line));
                try {
                    $res["datetime"] = Carbon::parse($dateStr);
                } catch (\Exception $e) {}
            } elseif (preg_match("/^؜?(\d{2}\/\d{1,2}\/\d{2})\s+(\d{1,2}:\d{2})/u", $line, $m)) {
                try {
                    $res["datetime"] = Carbon::createFromFormat("y/n/d H:i", $m[1] . " " . $m[2]);
                } catch (\Exception $e) {}
            } else {
                if (!$res["description"]) {
                    $res["description"] = $line;
                }
            }
        }

        return $res;
    }
}
