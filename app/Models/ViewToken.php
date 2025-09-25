<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewToken extends Model
{
    protected $fillable = ['name','token','card_id','bound_fingerprint','bound_at','is_active'];
    protected $casts = ['bound_at'=>'datetime'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
