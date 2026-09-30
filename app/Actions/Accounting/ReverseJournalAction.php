<?php

namespace App\Actions\Accounting;

use App\Enums\JournalStatus;
use App\Exceptions\Accounting\InvalidJournalException;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;

final class ReverseJournalAction
{
     public function __construct(
        private readonly PostJournalAction $postJournal,
    ) {
    }

    public function execute(
        Journal $journal,
        string $userId,
        ?string $reason = null
    ): Journal {
        return DB::transaction(function () use (
            $journal,
            $userId,
            $reason
        ) {

            $journal = Journal::query()
                ->with(['entries'])
                ->lockForUpdate()
                ->findOrFail($journal->id);

            if (
                $journal->status !== JournalStatus::Posted
            ) {
                throw new InvalidJournalException(
                    'Only posted journals can be reversed.'
                );
            }


            $reversal = Journal::create([
                'journal_number' => $this->generateNumber(),
                'journal_sequence' => $this->nextSequence(),

                'journal_date' => now()->toDateString(),

                'reference_type' => Journal::class,
                'reference_id' => $journal->id,

                'description' => $reason
                    ?: "Reversal of {$journal->journal_number}",

                'status' => JournalStatus::Draft->value,

                'created_by' => $userId,
            ]);

            foreach ($journal->entries as $entry) {
                $reversal->entries()->create([
                    'account_id' => $entry->account_id,
                    'currency_id' => $entry->currency_id,

                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                    'exchange_rate' => $entry->exchange_rate,
                    'base_debit' => $entry->base_credit,
                    'base_credit' => $entry->base_debit,
                    'description' => "Reversal of {$journal->journal_number}",
                ]);
            }

            $posted = $this->postJournal->execute( $reversal );

            $journal->update([
                'status' => JournalStatus::Reversed,
            ]);

            return $posted;
        });
    }

    private function nextSequence(): int
    {
        return ((int) Journal::query()
            ->max('journal_sequence')) + 1;
    }

    private function generateNumber(): string
    {
        return 'JV-' . str_pad(
            $this->nextSequence(),
            8,
            '0',
            STR_PAD_LEFT
        );
    }
}
