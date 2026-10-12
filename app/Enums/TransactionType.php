<?php

namespace App\Enums;

enum TransactionType: string
{
    case ReceiveFromCustomer = 'receive_from_customer';
    case RefundToCustomer = 'refund_to_customer';
    case PayVendor = 'pay_vendor';
    case ReceiveFromVendor = 'receive_from_vendor';
    case HawalaReceipt = 'hawala_receipt';
    case HawalaPayment = 'hawala_payment';
    // case EmployeePayment = 'employee_payment';
    // case ReceiveFromOwner = 'receive_from_owner';
    // case PayOwner = 'pay_owner';
    // case OtherReceipt = 'other_receipt';
    // case OtherPayment = 'other_payment';

    public function description(): string
    {
        return match ($this) {
            self::ReceiveFromCustomer => 'Receive money from a customer',
            self::RefundToCustomer => 'Return money to a customer',
            self::PayVendor => 'Pay a vendor',
            self::ReceiveFromVendor => 'Receive a refund or other payment from a vendor',
            self::HawalaReceipt => 'Receive money from a Hawala office',
            self::HawalaPayment => 'Send money to a Hawala office',
            // self::EmployeePayment => 'Pay salary, advance, or other employee dues',
            // self::ReceiveFromOwner => 'Receive owner capital or funding',
            // self::PayOwner => 'Pay an owner withdrawal or other amount due',
            // self::OtherReceipt => 'Receive money for another reason',
            // self::OtherPayment => 'Pay money for another reason'
        };
    }

    public static function descriptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $type) => [
                $type->value => $type->description(),
            ])
            ->toArray();
    }
}
