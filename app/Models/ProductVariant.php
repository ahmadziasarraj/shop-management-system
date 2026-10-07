<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeInStock($query)
    {
        return $query
            ->withSum('stockItems', 'quantity_on_hand')
            ->having('stock_items_sum_quantity_on_hand', '>', 0);
    }

    public function inStockItems()
    {
        return $this->hasMany(StockItem::class)->where('quantity_on_hand', '>', 0);
    }

    public function getSelectLabelAttribute(): string
    {
        return sprintf(
            '%s — %s',
            $this->product?->name ?? 'Unknown Product',
            $this->name,
        );
    }



    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function unitMeasurement(): BelongsTo
    {
        return $this->belongsTo(UnitMeasurement::class);
    }
    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function stockMmovements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class,
            'product_variant_id'
        );
    }
    public function stockBalances(): HasMany
    {
        return $this->hasMany(
            StockBalance::class,
            'product_variant_id'
        );
    }
}
