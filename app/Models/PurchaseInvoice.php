<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseInvoiceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'exchange_rate' => 'decimal:10',
            'subtotal' => 'decimal:10',
            'discount_amount' => 'decimal:10',
            'total_amount' => 'decimal:10',
            'paid_amount' => 'decimal:10',
            'base_paid_amount' => 'decimal:10',
            'base_subtotal' => 'decimal:10',
            'base_discount_amount' => 'decimal:10',
            'base_total_amount' => 'decimal:10',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'status' => PurchaseInvoiceStatus::class,
        ];
    }
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function hawalaOffice(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'hawala_office_id');
    }
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class);
    }
    public function purchaseReceipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
    public function landedCosts(): HasMany
    {
        return $this->hasMany(LandedCost::class);
    }
}
