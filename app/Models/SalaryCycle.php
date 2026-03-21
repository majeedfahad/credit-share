<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SalaryCycle extends Model
{
    protected $fillable = ["start_date", "end_date", "salary_amount", "budget", "budget_alert_threshold", "is_active"];
    
    protected $casts = [
        "start_date" => "date",
        "end_date" => "date",
        "salary_amount" => "decimal:2",
        "budget" => "decimal:2",
        "budget_alert_threshold" => "integer",
        "is_active" => "boolean",
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function categoryBudgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return (float) $this->payments()->sum("amount");
    }

    public function getRemainingBudgetAttribute(): ?float
    {
        if (!$this->budget) return null;
        return $this->budget - $this->total_spent;
    }

    public function getBudgetPercentageAttribute(): ?float
    {
        if (!$this->budget || $this->budget == 0) return null;
        return round(($this->total_spent / $this->budget) * 100, 1);
    }

    public function getDaysRemainingAttribute(): int
    {
        return (int) max(0, now()->diffInDays($this->end_date, false));
    }

    public static function current(): ?self
    {
        return self::where("is_active", true)
            ->where("start_date", "<=", now())
            ->where("end_date", ">=", now())
            ->first();
    }

    public static function findOrCreateForDate(Carbon $date): self
    {
        // Calculate the expected cycle dates first
        if ($date->day >= 25) {
            $startDate = $date->copy()->day(25);
            $endDate = $date->copy()->addMonth()->day(24);
        } else {
            $startDate = $date->copy()->subMonth()->day(25);
            $endDate = $date->copy()->day(24);
        }

        // Use firstOrCreate with exact dates to prevent race condition duplicates
        return self::firstOrCreate(
            [
                "start_date" => $startDate,
                "end_date" => $endDate,
            ],
            [
                "is_active" => true,
            ]
        );
    }
}
