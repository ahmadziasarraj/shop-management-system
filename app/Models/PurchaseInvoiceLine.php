<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseInvoiceLine extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:10',
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:10',
            'total_cost' => 'decimal:10',
            'base_unit_cost' => 'decimal:10',
            'base_total_cost' => 'decimal:10',
        ];
    }

    public function invoice() : BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }
    public function productVariant() : BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function purchaseReceipt() : HasOne
    {
        return $this->hasOne(PurchaseReceiptLine::class);
    }
    public function costLayers() : HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }
    public function stockItems() : HasMany
    {
        return $this->hasMany(StockItem::class);
    }
}
