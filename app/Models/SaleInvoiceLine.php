<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleInvoiceLine extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:10',
            'quantity' => 'decimal:6',
            'unit_price' => 'decimal:10',
            'discount_amount' => 'decimal:10',
            'line_total' => 'decimal:10',
            'base_unit_price' => 'decimal:10',
            'base_discount_amount' => 'decimal:10',
            'base_line_total' => 'decimal:10',
            'cogs_total' => 'decimal:10',
            'units_costs' => 'array',
        ];
    }

    public function invoice() : BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class);
    }
    public function productVariant() : BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
