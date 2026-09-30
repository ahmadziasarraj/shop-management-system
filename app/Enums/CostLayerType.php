<?php

namespace App\Enums;

enum CostLayerType: string
{
    case LandedCost = 'landed_cost';
    case Revaluation = 'revaluation';
    case Correction = 'correction';

    public function label(): string
    {
        return __("enums.landed_cost_payment_status.{$this->value}");
    }
}
