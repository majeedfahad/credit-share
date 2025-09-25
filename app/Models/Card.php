<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    protected $fillable = ['name','last4','type','current_balance','currency','is_active'];
    protected $casts = ['current_balance' => 'decimal:2'];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function viewTokens(): HasMany
    {
        return $this->hasMany(ViewToken::class);
    }
}
