<?php

namespace App\Services\Accounting;

use App\Models\Account;
use Carbon\Carbon;

class AccountBalanceService
{
    public function balance(
        Account $account,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): float {

        $query = $account->journalEntries()
            ->whereHas('journal', function ($query) {
                $query->where('status', 'posted');
            });

        if ($from) {
            $query->whereHas('journal', function ($query) use ($from) {
                $query->whereDate(
                    'journal_date',
                    '>=',
                    $from
                );
            });
        }

        if ($to) {
            $query->whereHas('journal', function ($query) use ($to) {
                $query->whereDate(
                    'journal_date',
                    '<=',
                    $to
                );
            });
        }

        $debit = (float) $query->sum('base_debit');

        $credit = (float) $query->sum('base_credit');

        return match ($account->normal_balance->value) {

            'debit' => $debit - $credit,

            'credit' => $credit - $debit,

        };
    }
}
