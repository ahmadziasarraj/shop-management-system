<?php

namespace App\Actions\Accounting;

use App\Data\Accounting\JournalData;
use App\Models\Journal;
use App\Services\Accounting\AccountingService;

final class CreateJournalAction
{
    public function __construct(
        private readonly AccountingService $accounting,
    ) {
    }

    public function execute(
        JournalData $data
    ): Journal {
        return $this->accounting->createJournal($data);
    }
}
