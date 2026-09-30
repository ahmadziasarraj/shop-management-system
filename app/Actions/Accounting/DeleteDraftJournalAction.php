<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Exceptions\Accounting\InvalidJournalException;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;

final class DeleteDraftJournalAction
{
    public function execute(
        Journal $journal
    ): void {

        DB::transaction(function () use ($journal) {

            if (
                $journal->status !==
                JournalStatus::Draft->value
            ) {
                throw new InvalidJournalException(
                    'Only draft journals can be deleted.'
                );
            }

            $journal->delete();
        });
    }
}
