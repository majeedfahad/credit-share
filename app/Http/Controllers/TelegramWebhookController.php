<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Category;
use App\Models\MerchantCategory;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook secret
        $secret = env('TELEGRAM_WEBHOOK_SECRET');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            Log::warning('Telegram webhook: invalid secret');
            return response()->json(['ok' => false], 403);
        }

        $data = $request->all();

        // Handle callback queries (inline button clicks)
        if (isset($data['callback_query'])) {
            return $this->handleCallbackQuery($data['callback_query']);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleCallbackQuery(array $callbackQuery): \Illuminate\Http\JsonResponse
    {
        $callbackData = $callbackQuery['data'] ?? '';
        $callbackQueryId = $callbackQuery['id'] ?? '';

        // Parse classify:{payment_id}:{category_id}
        if (!preg_match('/^classify:(\d+):(\d+)$/', $callbackData, $matches)) {
            return response()->json(['ok' => true]);
        }

        $paymentId = (int) $matches[1];
        $categoryId = (int) $matches[2];

        $payment = Payment::find($paymentId);
        $category = Category::find($categoryId);

        if (!$payment || !$category) {
            Log::warning('Telegram webhook: payment or category not found', [
                'payment_id' => $paymentId,
                'category_id' => $categoryId,
            ]);
            return response()->json(['ok' => true]);
        }

        // Update payment category
        $payment->category_id = $category->id;
        $payment->save();

        // Save merchant pattern for future auto-classification
        if ($payment->merchant) {
            MerchantCategory::updateOrCreate(
                ['merchant_pattern' => mb_strtolower($payment->merchant)],
                ['category_id' => $category->id]
            );
        }

        // Answer callback query
        $telegram = new TelegramService();
        $telegram->answerCallbackQuery(
            $callbackQueryId,
            "✅ تم تصنيف العملية: {$category->name_ar}"
        );

        Log::info('Payment classified via Telegram', [
            'payment_id' => $paymentId,
            'category' => $category->name_ar,
        ]);

        return response()->json(['ok' => true]);
    }
}
