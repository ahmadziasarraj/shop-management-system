<?php

namespace App\Exceptions\Accounting;

use RuntimeException;

class JournalNotBalancedException extends RuntimeException
{
    public function __construct(
        public readonly float $debit,
        public readonly float $credit,
    ) {
        parent::__construct(
            sprintf(
                'Journal is not balanced. Debit: %s, Credit: %s.',
                $debit,
                $credit
            )
        );
    }
}
