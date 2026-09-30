<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Unpaid = 'unpaid';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return __("enums.landed_cost_payment_status.{$this->value}");
    }
}
