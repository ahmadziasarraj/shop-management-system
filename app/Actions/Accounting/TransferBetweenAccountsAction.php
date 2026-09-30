<?php

namespace App\Actions\Accounting;

use App\Data\Accounting\JournalData;
use App\Data\Accounting\JournalLineData;
use App\Models\Account;
use App\Models\Currency;
use App\Services\Accounting\AccountingService;

final class TransferBetweenAccountsAction
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {
    }

    public function execute(
        Account $fromAccount,
        Account $toAccount,
        Currency $currency,
        float $amount,
        float $exchangeRate,
        string $date,
        string $userId,
        ?string $description = null,
    ) {
        $lines = collect([
            new JournalLineData(
                account: $toAccount,
                currency: $currency,
                debit: $amount,
                exchangeRate: $exchangeRate,
            ),

            new JournalLineData(
                account: $fromAccount,
                currency: $currency,
                credit: $amount,
                exchangeRate: $exchangeRate,
            ),
        ]);

        return $this->accounting->createJournal(
            new JournalData(
                journalDate: $date,
                lines: $lines,
                description:
                    $description ?? 'Account transfer',
                createdBy: $userId,
            )
        );
    }
}
