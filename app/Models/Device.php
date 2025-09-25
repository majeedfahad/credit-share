<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = ['name','api_token','device_type','is_active'];
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
