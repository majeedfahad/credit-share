<?php

namespace App\Console\Commands;

use App\Models\SalaryCycle;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckBudget extends Command
{
    protected $signature = 'pay:check-budget';
    protected $description = 'Check budget usage and send Telegram alert if threshold exceeded';

    public function handle(): int
    {
        $cycle = SalaryCycle::current();

        if (!$cycle) {
            $this->info('No active salary cycle found.');
            return 0;
        }

        if (!$cycle->budget || $cycle->budget <= 0) {
            $this->info('No budget set for current cycle.');
            return 0;
        }

        $spent = $cycle->total_spent;
        $budget = (float) $cycle->budget;
        $threshold = $cycle->budget_alert_threshold ?? 80;
        $pct = round(($spent / $budget) * 100, 1);

        if ($pct < $threshold) {
            $this->info("Budget at {$pct}% — below threshold ({$threshold}%).");
            return 0;
        }

        // Avoid duplicate alerts per cycle
        $cacheKey = "budget_alert_sent:{$cycle->id}:{$threshold}";
        if (Cache::has($cacheKey)) {
            $this->info('Alert already sent for this cycle.');
            return 0;
        }

        $remaining = $budget - $spent;
        $daysRemaining = $cycle->days_remaining;

        $message = "⚠️ تنبيه ميزانية!\n";
        $message .= "صرفت {$spent} من {$budget} ريال ({$pct}%)\n";
        $message .= "متبقي: {$remaining} ريال\n";
        $message .= "باقي {$daysRemaining} يوم على الراتب";

        // Calculate daily pace
        $daysElapsed = max(1, now()->diffInDays($cycle->start_date));
        $dailyAvg = round($spent / $daysElapsed, 1);
        $projected = $dailyAvg * ($daysElapsed + $daysRemaining);

        if ($projected > $budget) {
            $message .= "\n\n⚠️ بمعدلك الحالي ({$dailyAvg} ريال/يوم) راح تتجاوز الميزانية!";
        }

        try {
            $telegram = new TelegramService();
            $telegram->sendMessage($message);
            Cache::put($cacheKey, true, $cycle->end_date);
            $this->info('Budget alert sent.');
            Log::info('Budget alert sent', ['cycle_id' => $cycle->id, 'pct' => $pct]);
        } catch (\Throwable $e) {
            Log::error('Budget alert failed: ' . $e->getMessage());
            $this->error('Failed to send alert.');
            return 1;
        }

        return 0;
    }
}
