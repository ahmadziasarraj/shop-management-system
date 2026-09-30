<?php

namespace App\Actions\Accounting;

use App\Enums\SystemAccount;
use App\Models\SystemAccountSetting;

final class ConfigureSystemAccountsAction
{
    public function execute(
        SystemAccount $key,
        string $accountId
    ): SystemAccountSetting {

        return SystemAccountSetting::updateOrCreate(
            [
                'key' => $key->value,
            ],
            [
                'account_id' => $accountId,
            ]
        );
    }
}
