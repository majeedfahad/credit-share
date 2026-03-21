<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        "card_id", "device_id", "amount", "balance_after", "card_last4",
        "merchant", "description", "card_type", "raw_text", "note", 
        "received_at", "category_id", "salary_cycle_id"
    ];
    
    protected $casts = [
        "received_at" => "datetime",
        "amount" => "decimal:2",
        "balance_after" => "decimal:2",
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
    
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    public function salaryCycle(): BelongsTo
    {
        return $this->belongsTo(SalaryCycle::class);
    }
    
    public function autoClassify(): void
    {
        if ($this->category_id) return;
        
        $category = MerchantCategory::findCategoryForMerchant($this->merchant);
        if ($category) {
            $this->category_id = $category->id;
            $this->save();
        }
    }
}
