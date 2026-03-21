<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Category;
use App\Models\SalaryCycle;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonthlyReport extends Command
{
    protected $signature = 'pay:monthly-report';
    protected $description = 'Send monthly spending report for current salary cycle via Telegram';

    public function handle(): int
    {
        $cycle = SalaryCycle::current();
        if (!$cycle) {
            $this->info('No active salary cycle found.');
            return 0;
        }

        $payments = Payment::where('salary_cycle_id', $cycle->id)->get();
        $totalSpent = $payments->sum('amount');
        $budget = (float) $cycle->budget;

        // Previous cycle
        $prevCycle = SalaryCycle::where('end_date', '<', $cycle->start_date)
            ->orderByDesc('end_date')
            ->first();
        $prevPayments = $prevCycle ? Payment::where('salary_cycle_id', $prevCycle->id)->get() : collect();
        $prevTotal = $prevPayments->sum('amount');

        $message = "📅 *التقرير الشهري*\n";
        $message .= "الدورة: " . $cycle->start_date->format('Y/m/d') . " - " . $cycle->end_date->format('Y/m/d') . "\n\n";

        // Total vs budget
        $message .= "💵 *إجمالي المصروفات:* {$totalSpent} ريال\n";
        if ($budget > 0) {
            $pct = round(($totalSpent / $budget) * 100, 1);
            $remaining = $budget - $totalSpent;
            $message .= "📊 *الميزانية:* {$budget} ريال ({$pct}% مستخدم)\n";
            $message .= "💰 *المتبقي:* {$remaining} ريال\n";
        }

        // Compare with previous cycle
        if ($prevTotal > 0) {
            $change = round((($totalSpent - $prevTotal) / $prevTotal) * 100, 1);
            $arrow = $change >= 0 ? '↑' : '↓';
            $message .= "\n📈 *مقارنة بالدورة السابقة:* {$arrow} " . abs($change) . "% ({$prevTotal} ريال)\n";
        }

        // Category breakdown
        $message .= "\n📂 *التصنيفات:*\n";
        $categories = Category::all()->keyBy('id');
        $byCategory = $payments->groupBy('category_id')
            ->map(fn($items) => $items->sum('amount'))
            ->sortDesc();

        foreach ($byCategory as $catId => $catTotal) {
            $cat = $categories->get($catId);
            $icon = $cat ? ($cat->icon ?? '📁') : '❓';
            $name = $cat ? $cat->name_ar : 'غير مصنف';
            $catPct = $totalSpent > 0 ? round(($catTotal / $totalSpent) * 100, 1) : 0;
            $message .= "{$icon} {$name}: {$catTotal} ريال ({$catPct}%)\n";
        }

        // Top 10 merchants
        $message .= "\n🏪 *أكثر 10 تجار:*\n";
        $topMerchants = $payments->groupBy('merchant')
            ->map(fn($items) => $items->sum('amount'))
            ->sortDesc()
            ->take(10);

        $rank = 1;
        foreach ($topMerchants as $merchant => $total) {
            $name = $merchant ?: 'غير معروف';
            $message .= "{$rank}. {$name}: {$total} ريال\n";
            $rank++;
        }

        // Daily average
        $daysInCycle = max(1, $cycle->start_date->diffInDays(now()));
        $dailyAvg = round($totalSpent / $daysInCycle, 1);
        $message .= "\n📈 *المعدل اليومي:* {$dailyAvg} ريال/يوم\n";

        // Biggest transaction
        $biggest = $payments->sortByDesc('amount')->first();
        if ($biggest) {
            $merchant = $biggest->merchant ?: 'غير معروف';
            $message .= "🔝 *أكبر عملية:* {$biggest->amount} ريال لدى {$merchant}\n";
        }

        // Transaction count
        $message .= "🔢 *عدد العمليات:* " . $payments->count() . " عملية";

        try {
            $telegram = new TelegramService();
            $telegram->sendMessage($message);
            $this->info('Monthly report sent.');
            Log::info('Monthly report sent', ['cycle_id' => $cycle->id, 'total' => $totalSpent]);
        } catch (\Throwable $e) {
            Log::error('Monthly report failed: ' . $e->getMessage());
            $this->error('Failed to send report.');
            return 1;
        }

        return 0;
    }
}
