<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ["name", "name_ar", "icon", "color", "sort_order"];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function merchantPatterns(): HasMany
    {
        return $this->hasMany(MerchantCategory::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(CategoryBudget::class);
    }
}
