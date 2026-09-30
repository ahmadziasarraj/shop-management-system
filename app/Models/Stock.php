<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockMovements()
    {
        return $this->hasMany(
            StockMovement::class,
            'stock_id'
        );
    }
     public function inventoryBalances()
    {
        return $this->hasMany(
            StockBalance::class,
            'stock_id'
        );
    }
}
