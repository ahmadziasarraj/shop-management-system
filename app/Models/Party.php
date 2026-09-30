<?php

namespace App\Models;

use App\Enums\PartyType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Party extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'type' => PartyType::class,
            'phone' => 'array',
            'opening_payable' => 'decimal:6',
            'opening_receivable' => 'decimal:6',
        ];
    }

    public function ledgerEntries()
    {
        return $this->hasMany(
            PartyLedgerEntry::class
        );
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }
    public function currenciesInLedgerEntries(): HasManyThrough
    {
        return $this->hasManyThrough(
            Currency::class,
            PartyLedgerEntry::class,
            'party_id',
            'id',
            'id',
            'currency_id'
        )->distinct();
    }
}
