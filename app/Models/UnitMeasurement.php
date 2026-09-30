<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class UnitMeasurement extends Model
{
    use HasUlids;

    public function productVariants()
    {
        return $this->hasMany(
            ProductVariant::class
        );
    }
}
