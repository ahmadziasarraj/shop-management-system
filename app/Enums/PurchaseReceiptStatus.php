<?php

namespace App\Enums;

enum PurchaseReceiptStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.purchase_receipt_status.{$this->value}");
    }
}
