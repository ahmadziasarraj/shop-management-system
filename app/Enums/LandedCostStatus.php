<?php

namespace App\Enums;

enum LandedCostStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return __("enums.landed_cost_status.{$this->value}");
    }
}
