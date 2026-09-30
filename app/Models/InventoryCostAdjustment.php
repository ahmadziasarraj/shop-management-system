<?php

namespace App\Models;

use App\Enums\CostLayerType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCostAdjustment extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'quantity_affected' => 'decimal:6',
            'unit_adjustment' => 'decimal:6',
            'amount' => 'decimal:10',
            'type' => CostLayerType::class,
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

    public function landedCost(): BelongsTo
    {
        return $this->belongsTo(LandedCost::class);
    }
    public function landedCostAllocation(): BelongsTo
    {
        return $this->belongsTo(LandedCostAllocation::class);
    }
}
