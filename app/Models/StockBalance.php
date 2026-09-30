<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{

    use HasUlids;

    protected $casts = [
        'quantity_on_hand' => 'decimal:6',
        'quantity_reserved' => 'decimal:6',
        'total_cost' => 'decimal:10',
        'average_cost' => 'decimal:10',
        'exchange_rate' => 'decimal:10',
        'base_total_cost' => 'decimal:10',
        'base_average_cost' => 'decimal:10',
    ];


    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getAvailableQuantityAttribute(): float
    {
        return max( 0, (float) $this->quantity_on_hand - (float) $this->quantity_reserved );
    }
}
