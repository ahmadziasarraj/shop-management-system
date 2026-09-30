<?php

namespace App\Models;

use App\Enums\PartyLedgerEntryType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PartyLedgerEntry extends Model
{
    use HasUlids;

    protected $casts = [
        'type' => PartyLedgerEntryType::class,

        'transaction_date' => 'date',

        'debit_amount' => 'decimal:6',
        'credit_amount' => 'decimal:6',

        'base_debit_amount' => 'decimal:6',
        'base_credit_amount' => 'decimal:6',

        'exchange_rate' => 'decimal:10',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }


    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
