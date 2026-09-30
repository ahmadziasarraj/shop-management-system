<?php

namespace App\Models;

use App\Enums\LandedCostStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandedCost extends Model
{
    use HasUlids;

    protected $casts = [
        'exchange_rate' => 'decimal:10',
        'amount' => 'decimal:10',
        'base_amount' => 'decimal:10',
        'paid_amount' => 'decimal:10',
        'base_paid_amount' => 'decimal:10',
        'status' => LandedCostStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
    public function hawalaOffice() : BelongsTo
    {
        return $this->belongsTo(Party::class, 'hawala_office_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(LandedCostAllocation::class);
    }

    public function costLayers() : HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }
    public function stockItems() : HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
