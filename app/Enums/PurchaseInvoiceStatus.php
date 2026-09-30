<?php

namespace App\Enums;

enum PurchaseInvoiceStatus:string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Confirmed = 'confirmed';
    case PartiallyReceived = 'partially_received';
    case FullyReceived = 'fully_received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.purchase_receipt_status.{$this->value}");
    }
}
