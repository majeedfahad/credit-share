<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantCategory extends Model
{
    protected $fillable = ["merchant_pattern", "category_id"];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public static function findCategoryForMerchant(?string $merchant): ?Category
    {
        if (!$merchant) return null;
        
        $merchant = mb_strtolower($merchant);
        
        $mapping = self::with("category")->get();
        
        foreach ($mapping as $map) {
            if (mb_strpos($merchant, mb_strtolower($map->merchant_pattern)) !== false) {
                return $map->category;
            }
        }
        
        return null;
    }
}
