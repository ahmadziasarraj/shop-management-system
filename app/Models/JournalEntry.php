<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:6',
            'credit' => 'decimal:6',
            'exchange_rate' => 'decimal:10',
            'base_debit' => 'decimal:6',
            'base_credit' => 'decimal:6',
        ];
    }

    public function journal()
    {
        return $this->belongsTo(
            Journal::class
        );
    }

    public function account()
    {
        return $this->belongsTo(
            Account::class
        );
    }

    public function currency()
    {
        return $this->belongsTo(
            Currency::class
        );
    }
}
