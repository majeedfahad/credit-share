<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = ["name", "last4", "type", "current_balance", "currency", "is_active", "parent_card_id"];
    
    protected $casts = ["current_balance" => "decimal:2"];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    public function viewTokens(): HasMany
    {
        return $this->hasMany(ViewToken::class);
    }
    
    public function parentCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, "parent_card_id");
    }
    
    public function childCards(): HasMany
    {
        return $this->hasMany(Card::class, "parent_card_id");
    }
    
    public function isSubCard(): bool
    {
        return $this->parent_card_id !== null;
    }
    
    public function getTotalBalanceAttribute(): float
    {
        $total = (float) $this->current_balance;
        foreach ($this->childCards as $child) {
            $total += (float) $child->current_balance;
        }
        return $total;
    }
}
