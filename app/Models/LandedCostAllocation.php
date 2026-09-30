<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandedCostAllocation extends Model
{
    use HasUlids;

    protected $casts = [
        'basis_value' => 'decimal:10',
        'allocated_amount' => 'decimal:10',
        'base_allocated_amount' => 'decimal:10',
        'quantity_purchased' => 'decimal:6',
        'quantity_affected' => 'decimal:6',
        'inventory_amount' => 'decimal:10',
    ];

     public function landedCost(): BelongsTo
    {
        return $this->belongsTo(LandedCost::class);
    }

    public function purchaseInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoiceLine::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
    public function inventoryCostAdjustments(): HasMany
    {
        return $this->hasMany(InventoryCostAdjustment::class);
    }
}
