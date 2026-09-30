<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function variants()
    {
        return $this->hasMany(
            ProductVariant::class,
            'product_id'
        );
    }
}
