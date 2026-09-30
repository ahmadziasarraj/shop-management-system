<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case HawalaOffice = 'hawala_office';
    // case Credit = 'credit';


    public function label(): string
    {
        return __("enums.landed_cost_payment_status.{$this->value}");
    }
}
