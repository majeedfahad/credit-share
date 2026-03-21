<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;
    protected string $defaultChatId;
    protected string $apiBase;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', ''));
        $this->defaultChatId = config('services.telegram.chat_id', env('TELEGRAM_CHAT_ID', ''));
        $this->apiBase = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $text, ?string $chatId = null): bool
    {
        $chatId = $chatId ?? $this->defaultChatId;

        if (empty($this->botToken) || empty($chatId)) {
            Log::warning('TelegramService: bot token or chat ID not configured');
            return false;
        }

        try {
            $response = Http::post("{$this->apiBase}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            if (!$response->successful()) {
                Log::error('TelegramService: sendMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramService: exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendMessageWithButtons(string $text, array $buttons, ?string $chatId = null): bool
    {
        $chatId = $chatId ?? $this->defaultChatId;

        if (empty($this->botToken) || empty($chatId)) {
            Log::warning('TelegramService: bot token or chat ID not configured');
            return false;
        }

        // Build inline keyboard rows (2 buttons per row)
        $rows = [];
        $currentRow = [];
        foreach ($buttons as $button) {
            $currentRow[] = $button;
            if (count($currentRow) >= 2) {
                $rows[] = $currentRow;
                $currentRow = [];
            }
        }
        if (!empty($currentRow)) {
            $rows[] = $currentRow;
        }

        try {
            $response = Http::post("{$this->apiBase}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $rows,
                ]),
            ]);

            if (!$response->successful()) {
                Log::error('TelegramService: sendMessageWithButtons failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramService: exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text): bool
    {
        try {
            $response = Http::post("{$this->apiBase}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => false,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('TelegramService: answerCallbackQuery exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
