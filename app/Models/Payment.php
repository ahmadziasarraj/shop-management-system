<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasUlids;

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'payment_date' => 'date',

        'amount' => 'decimal:10',
        'base_amount' => 'decimal:10',
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
