<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryBudget extends Model
{
    protected $fillable = ['category_id', 'salary_cycle_id', 'budget_amount'];

    protected $casts = [
        'budget_amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function salaryCycle(): BelongsTo
    {
        return $this->belongsTo(SalaryCycle::class);
    }
}
