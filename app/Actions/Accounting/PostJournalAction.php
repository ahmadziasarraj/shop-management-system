<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Exceptions\Accounting\InvalidJournalException;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;

final class PostJournalAction
{
    public function execute( Journal $journal ): Journal {
        return DB::transaction(function () use ( $journal ) {

            $journal = Journal::query()
                ->with([
                    'entries.account',
                    'entries.currency',
                ])
                ->lockForUpdate()
                ->findOrFail($journal->id);

            if (
                $journal->status !== JournalStatus::Draft
            ) {
                throw new InvalidJournalException(
                    'Only draft journals can be posted.'
                );
            }

            if ($journal->entries->isEmpty()) {
                throw new InvalidJournalException(
                    'Cannot post an empty journal.'
                );
            }

            $debit = $journal->entries->sum(
                'base_debit'
            );

            $credit = $journal->entries->sum(
                'base_credit'
            );

            if (abs($debit - $credit) > 0.000001) {
                throw new InvalidJournalException(
                    'Journal is not balanced.'
                );
            }

            $journal->update([
                'status' => JournalStatus::Posted
            ]);

            return $journal->fresh([
                'entries'
            ]);
        });
    }
}
