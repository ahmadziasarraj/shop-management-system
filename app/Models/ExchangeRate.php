<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ExchangeRate extends Model
{
    use HasUlids;

    protected $casts = [
        'rate' => 'decimal:10',
        'effective_at' => 'datetime',
    ];

     /**
     * Get the latest exchange rate for each currency pair.
     */
    public static function latestForPairs(string $fromCurrencyId): Collection
    {
        return static::query()
            ->with(['fromCurrency', 'toCurrency'])
            ->where('from_currency_id', $fromCurrencyId)
            ->latest('effective_at')
            ->get()
            ->unique(
                fn ($rate) => "{$rate->from_currency_id}-{$rate->to_currency_id}"
            )
            ->values();
    }

    /**
     * Get the source currency.
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    /**
     * Get the target currency.
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * Get the user who created the exchange rate.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
