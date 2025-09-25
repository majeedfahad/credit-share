<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'card_id','device_id','amount','balance_after','card_last4',
        'merchant','raw_text','note','received_at'
    ];
    protected $casts = ['received_at'=>'datetime'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
    public function device() : BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
