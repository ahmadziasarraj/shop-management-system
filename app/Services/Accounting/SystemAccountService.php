<?php

namespace App\Services\Accounting;

use App\Enums\SystemAccount;
use App\Models\Account;
use App\Models\SystemAccountSetting;
use RuntimeException;

final class SystemAccountService
{
    public function get( SystemAccount $key ): Account {

        $setting = SystemAccountSetting::query()
            ->with('account')
            ->where('key', $key->value)
            ->first();

        if (!$setting) {
            throw new RuntimeException(
                "System account [{$key->value}] is not configured."
            );
        }

        return $setting->account;
    }
}
