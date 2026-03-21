<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedPayment extends Model
{
    protected $fillable = [
        "device_id", "raw_text", "error_message", 
        "retry_count", "last_retry_at", "is_processed"
    ];
    
    protected $casts = [
        "last_retry_at" => "datetime",
        "is_processed" => "boolean",
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
