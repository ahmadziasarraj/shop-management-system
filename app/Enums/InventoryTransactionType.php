<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case Opening = 'opening';
    case PurchaseReceipt = 'purchase_receipt';
    case SaleIssue = 'sale_issue';
    case PurchaseReturn = 'purchase_return';
    case SaleReturn = 'sale_return';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case Damage = 'damage';
    case WriteOff = 'write_off';
    public function label(): string
    {
        return __("enums.inventory_transaction_type.{$this->value}");
    }

    public function isIncrease(): bool
    {
        return match ($this) {
            self::Opening,
            self::PurchaseReceipt,
            self::SaleReturn,
            self::TransferIn,
            self::AdjustmentIn => true,

            default => false,
        };
    }

    public function isDecrease(): bool
    {
        return ! $this->isIncrease();
    }
}
