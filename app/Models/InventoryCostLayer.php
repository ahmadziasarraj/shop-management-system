<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCostLayer extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'quantity_consumed' => 'decimal:6',
            'unit_cost' => 'decimal:10',
            'landed_cost_per_unit' => 'decimal:10',
            'total_unit_cost' => 'decimal:10',
            'total_cost' => 'decimal:10',
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
    public function purchaseInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceLine::class);
    }
    public function landedCost(): BelongsTo
    {
        return $this->belongsTo(LandedCost::class);
    }
}
