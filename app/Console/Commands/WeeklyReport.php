<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Category;
use App\Models\SalaryCycle;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WeeklyReport extends Command
{
    protected $signature = 'pay:weekly-report';
    protected $description = 'Send weekly spending report via Telegram';

    public function handle(): int
    {
        $endDate = now();
        $startDate = now()->subDays(7);
        $prevStart = now()->subDays(14);
        $prevEnd = now()->subDays(7);

        // Current week payments
        $payments = Payment::whereBetween('received_at', [$startDate, $endDate])->get();
        $prevPayments = Payment::whereBetween('received_at', [$prevStart, $prevEnd])->get();

        $totalSpent = $payments->sum('amount');
        $prevTotal = $prevPayments->sum('amount');

        if ($payments->isEmpty()) {
            $this->info('No payments this week.');
            return 0;
        }

        $message = "📊 *التقرير الأسبوعي*\n";
        $message .= "من " . $startDate->format('m/d') . " إلى " . $endDate->format('m/d') . "\n\n";

        // Total
        $message .= "💵 *الإجمالي:* {$totalSpent} ريال";
        if ($prevTotal > 0) {
            $change = round((($totalSpent - $prevTotal) / $prevTotal) * 100, 1);
            $arrow = $change >= 0 ? '↑' : '↓';
            $message .= " ({$arrow} " . abs($change) . "%)";
        }
        $message .= "\n\n";

        // By category
        $message .= "📂 *حسب التصنيف:*\n";
        $categories = Category::all()->keyBy('id');
        $byCategory = $payments->groupBy('category_id');
        $prevByCategory = $prevPayments->groupBy('category_id');

        $sorted = $byCategory->map(fn($items) => $items->sum('amount'))->sortDesc();

        foreach ($sorted as $catId => $catTotal) {
            $cat = $categories->get($catId);
            $icon = $cat ? ($cat->icon ?? '📁') : '❓';
            $name = $cat ? $cat->name_ar : 'غير مصنف';
            $line = "{$icon} {$name}: {$catTotal} ريال";

            $prevCatTotal = isset($prevByCategory[$catId]) ? $prevByCategory[$catId]->sum('amount') : 0;
            if ($prevCatTotal > 0) {
                $change = round((($catTotal - $prevCatTotal) / $prevCatTotal) * 100, 1);
                $arrow = $change >= 0 ? '↑' : '↓';
                $line .= " ({$arrow} " . abs($change) . "%)";
            }
            $message .= $line . "\n";
        }

        // Top 5 merchants
        $message .= "\n🏪 *أكثر 5 تجار:*\n";
        $topMerchants = $payments->groupBy('merchant')
            ->map(fn($items) => $items->sum('amount'))
            ->sortDesc()
            ->take(5);

        $rank = 1;
        foreach ($topMerchants as $merchant => $total) {
            $name = $merchant ?: 'غير معروف';
            $message .= "{$rank}. {$name}: {$total} ريال\n";
            $rank++;
        }

        // Daily average
        $dailyAvg = round($totalSpent / 7, 1);
        $message .= "\n📈 *المعدل اليومي:* {$dailyAvg} ريال/يوم\n";

        // Budget info
        $cycle = SalaryCycle::current();
        if ($cycle && $cycle->budget) {
            $remaining = $cycle->remaining_budget;
            $daysLeft = $cycle->days_remaining;
            $message .= "\n💰 *الميزانية:*\n";
            $message .= "متبقي: {$remaining} ريال\n";
            $message .= "باقي {$daysLeft} يوم على الراتب\n";

            if ($daysLeft > 0) {
                $dailyBudget = round($remaining / $daysLeft, 1);
                $message .= "المسموح يومياً: {$dailyBudget} ريال";
            }
        }

        try {
            $telegram = new TelegramService();
            $telegram->sendMessage($message);
            $this->info('Weekly report sent.');
            Log::info('Weekly report sent', ['total' => $totalSpent]);
        } catch (\Throwable $e) {
            Log::error('Weekly report failed: ' . $e->getMessage());
            $this->error('Failed to send report.');
            return 1;
        }

        return 0;
    }
}
