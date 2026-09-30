<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptLine extends Model
{
    use HasUlids;

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:10',
        'total_cost' => 'decimal:10',

        'received_quantity' => 'decimal:6',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseReceipt::class,
            'purchase_receipt_id'
        );
    }

    public function purchaseInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseInvoiceLine::class
        );
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class
        );
    }
}
