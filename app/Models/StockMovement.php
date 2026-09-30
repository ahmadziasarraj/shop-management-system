<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'type' => InventoryTransactionType::class, // this changed from MovementType

            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:10',
            'total_cost' => 'decimal:10',
            'exchange_rate' => 'decimal:10',
            'base_unit_cost' => 'decimal:10',
            'base_total_cost' => 'decimal:10',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // public function parent()
    // {
    //     return $this->belongsTo(
    //         Account::class,
    //         'parent_id'
    //     );
    // }
}
