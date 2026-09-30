<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'quantity_consumed' => 'decimal:6',
            'quantity_reserved' => 'decimal:6',
            'quantity_on_hand' => 'decimal:6',

            'purchased_amount' => 'decimal:10',
            'unit_landed_cost' => 'decimal:10',
            'unit_total_cogs' => 'decimal:10',
            'total_inventory' => 'decimal:10',
            'current_inventory' => 'decimal:10',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
