<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\Payment;
use App\Models\SalaryCycle;
use App\Models\FailedPayment;
use App\Models\Category;
use Carbon\Carbon;

class SystemHealthController extends Controller
{
    public function status(Request $request)
    {
        $issues = [];
        $info = [];

        // 1. Check required env vars
        $requiredEnv = [
            'TELEGRAM_BOT_TOKEN' => 'توكن بوت تيليقرام',
            'TELEGRAM_CHAT_ID' => 'Chat ID تيليقرام',
            'TELEGRAM_WEBHOOK_SECRET' => 'سر حماية الـ webhook',
        ];

        $missingEnv = [];
        foreach ($requiredEnv as $key => $label) {
            if (empty(env($key))) {
                $missingEnv[$key] = $label;
            }
        }
        if (!empty($missingEnv)) {
            $issues[] = [
                'type' => 'missing_env',
                'severity' => 'critical',
                'message' => 'متغيرات بيئة ناقصة',
                'details' => $missingEnv,
            ];
        }

        // 2. Check pending migrations
        try {
            $ran = DB::table('migrations')->pluck('migration')->toArray();
            $migrationPath = database_path('migrations');
            $files = glob($migrationPath . '/*.php');
            $pending = [];
            foreach ($files as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($name, $ran)) {
                    $pending[] = $name;
                }
            }
            if (!empty($pending)) {
                $issues[] = [
                    'type' => 'pending_migrations',
                    'severity' => 'high',
                    'message' => 'فيه migrations ما انعملت',
                    'details' => $pending,
                ];
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'db_error',
                'severity' => 'critical',
                'message' => 'ما قدرت أتصل بقاعدة البيانات',
                'details' => $e->getMessage(),
            ];
        }

        // 3. Failed payments queue
        $failedCount = FailedPayment::where('is_processed', false)->count();
        if ($failedCount > 0) {
            $issues[] = [
                'type' => 'failed_payments',
                'severity' => 'medium',
                'message' => "فيه {$failedCount} عمليات فاشلة ما انعالجت",
                'details' => [
                    'count' => $failedCount,
                    'oldest' => FailedPayment::where('is_processed', false)
                        ->oldest()->first()?->created_at?->toDateTimeString(),
                ],
            ];
        }

        // 4. Unclassified payments
        $unclassifiedCount = Payment::whereNull('category_id')->count();
        if ($unclassifiedCount > 0) {
            $recentUnclassified = Payment::whereNull('category_id')
                ->orderByDesc('received_at')
                ->limit(5)
                ->get(['id', 'amount', 'merchant', 'received_at']);

            $issues[] = [
                'type' => 'unclassified_payments',
                'severity' => 'low',
                'message' => "{$unclassifiedCount} عملية بدون تصنيف",
                'details' => [
                    'count' => $unclassifiedCount,
                    'recent' => $recentUnclassified,
                ],
            ];
        }

        // 5. Current salary cycle info
        $cycle = SalaryCycle::current();
        if ($cycle) {
            $cycleInfo = [
                'start' => $cycle->start_date->format('Y-m-d'),
                'end' => $cycle->end_date->format('Y-m-d'),
                'days_remaining' => $cycle->days_remaining,
                'total_spent' => (float) $cycle->total_spent,
                'budget' => (float) $cycle->budget,
                'remaining_budget' => $cycle->remaining_budget,
                'budget_percentage' => $cycle->budget_percentage,
                'budget_alert_threshold' => $cycle->budget_alert_threshold ?? 80,
            ];

            // Daily pace
            $daysElapsed = max(1, $cycle->start_date->diffInDays(now()));
            $dailyAvg = round($cycle->total_spent / $daysElapsed, 2);
            $projectedTotal = round($dailyAvg * $cycle->start_date->diffInDays($cycle->end_date), 2);
            $cycleInfo['daily_average'] = $dailyAvg;
            $cycleInfo['projected_total'] = $projectedTotal;
            $cycleInfo['will_exceed_budget'] = $cycle->budget > 0 && $projectedTotal > $cycle->budget;

            if (!$cycle->budget || $cycle->budget == 0) {
                $issues[] = [
                    'type' => 'no_budget',
                    'severity' => 'medium',
                    'message' => 'الدورة الحالية بدون ميزانية محددة',
                ];
            }

            $info['current_cycle'] = $cycleInfo;
        } else {
            $issues[] = [
                'type' => 'no_active_cycle',
                'severity' => 'medium',
                'message' => 'ما فيه دورة راتب نشطة',
            ];
        }

        // 6. Last payment received
        $lastPayment = Payment::orderByDesc('received_at')->first();
        if ($lastPayment) {
            $info['last_payment'] = [
                'amount' => (float) $lastPayment->amount,
                'merchant' => $lastPayment->merchant,
                'received_at' => $lastPayment->received_at?->toDateTimeString(),
                'hours_ago' => $lastPayment->received_at ? round($lastPayment->received_at->diffInHours(now()), 1) : null,
            ];
        }

        // 7. Category stats
        $categoriesCount = Category::count();
        $merchantPatternsCount = DB::table('merchant_categories')->count();
        $info['classification'] = [
            'categories' => $categoriesCount,
            'merchant_patterns' => $merchantPatternsCount,
            'auto_classify_rate' => Payment::count() > 0 
                ? round(Payment::whereNotNull('category_id')->count() / Payment::count() * 100, 1) 
                : 0,
        ];

        // 8. Scheduler status
        $info['schedule'] = [
            'check_budget' => 'hourly',
            'weekly_report' => 'Saturdays 9:00 AM',
            'monthly_report' => '25th 10:00 AM',
            'cron_hint' => 'تأكد إن * * * * * cd /home/forge/pay.dt.sa && php artisan schedule:run >> /dev/null 2>&1 مضاف في crontab',
        ];

        // 9. Telegram webhook status
        if (!empty(env('TELEGRAM_BOT_TOKEN'))) {
            $info['telegram'] = [
                'bot_configured' => true,
                'webhook_url' => url('/api/telegram/webhook'),
                'webhook_needs_setup' => 'نادِ Telegram setWebhook API عشان تربط البوت بالـ endpoint',
            ];
        } else {
            $info['telegram'] = ['bot_configured' => false];
        }

        return response()->json([
            'status' => empty($issues) ? 'healthy' : 'needs_attention',
            'checked_at' => now()->toDateTimeString(),
            'issues' => $issues,
            'info' => $info,
        ]);
    }
}
