<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasUlids;
    protected function casts(): array
    {
        return [
            'account_type' => AccountType::class,
            'normal_balance' => NormalBalance::class,

            'is_group' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(
            Account::class,
            'parent_id'
        );
    }

    public function children()
    {
        return $this->hasMany(
            Account::class,
            'parent_id'
        );
    }

    public function journalEntries()
    {
        return $this->hasMany(
            JournalEntry::class
        );
    }

    public function systemAccounts()
    {
        return $this->hasMany(
            SystemAccountSetting::class
        );
    }
}
