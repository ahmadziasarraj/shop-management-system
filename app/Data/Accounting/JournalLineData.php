<?php

namespace App\Data\Accounting;

use App\Models\Account;
use App\Models\Currency;

final readonly class JournalLineData
{
    public function __construct(
        public Account $account,
        public Currency $currency,
        public float $debit = 0,
        public float $credit = 0,
        public float $exchangeRate = 1,
        public ?string $description = null,
    ) {
    }

    public function baseDebit(): float
    {
        return round(
            $this->debit / $this->exchangeRate,
            6
        );
    }

    public function baseCredit(): float
    {
        return round(
            $this->credit / $this->exchangeRate,
            6
        );
    }
}
