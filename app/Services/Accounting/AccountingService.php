<?php

namespace App\Services\Accounting;

use App\Data\Accounting\JournalData;
use App\Enums\JournalStatus;
use App\Exceptions\Accounting\InvalidJournalException;
use App\Exceptions\Accounting\JournalNotBalancedException;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function createJournal(
        JournalData $data
    ): Journal {
        return DB::transaction(function () use ($data) {

            $this->validateLines($data);
            $this->validateBalance($data);

            $journal = Journal::create([
                'journal_number' => $this->generateJournalNumber(),
                'journal_sequence' => $this->generateJournalSequence(),
                'journal_date' => $data->journalDate,
                'reference_type' => $data->referenceType,
                'reference_id' =>$data->referenceId,
                'description' => $data->description,
                'status' => JournalStatus::Draft,
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $line) {
                $journal->entries()->create([
                    'account_id' => $line->account->id,
                    'currency_id' => $line->currency->id,

                    'debit' => $line->debit,
                    'credit' => $line->credit,

                    'exchange_rate' => $line->exchangeRate,

                    'base_debit' => $line->baseDebit(),

                    'base_credit' => $line->baseCredit(),

                    'description' => $line->description,
                ]);
            }

            return $journal->load('entries');
        });
    }

    public function validateBalance(
        JournalData $data
    ): void {
        $debit = round(
            $data->lines->sum(
                fn ($line) => $line->baseDebit()
            ),
            6
        );

        $credit = round(
            $data->lines->sum(
                fn ($line) => $line->baseCredit()
            ),
            6
        );

        if (abs($debit - $credit) > 0.000001) {
            throw new JournalNotBalancedException(
                $debit,
                $credit
            );
        }
    }


    protected function validateLines(
        JournalData $data
    ): void {
        if ($data->lines->count() < 2) {
            throw new InvalidJournalException(
                'A journal must contain at least two lines.'
            );
        }

        foreach ($data->lines as $line) {

            if ($line->debit < 0 || $line->credit < 0) {
                throw new InvalidJournalException(
                    'Debit and credit cannot be negative.'
                );
            }

            if (
                ($line->debit > 0 && $line->credit > 0)
                // || ($line->debit == 0 && $line->credit == 0)
            ) {
                throw new InvalidJournalException(
                    'Each line must contain either debit or credit.'
                );
            }

            if ($line->account->is_group) {
                throw new InvalidJournalException(
                    "Account {$line->account->code} is a group account."
                );
            }

            if (!$line->account->is_active) {
                throw new InvalidJournalException(
                    "Account {$line->account->code} is inactive."
                );
            }

            if (!$line->currency->is_active) {
                throw new InvalidJournalException(
                    "Currency {$line->currency->code} is inactive."
                );
            }

            if ($line->exchangeRate <= 0) {
                throw new InvalidJournalException(
                    'Exchange rate must be greater than zero.'
                );
            }
        }
    }

    protected function generateJournalNumber(): string {
        $lastNumber = Journal::query()
            ->max('journal_sequence');

        return 'JV-' . str_pad(
            ($lastNumber ?? 0) + 1,
            8,
            '0',
            STR_PAD_LEFT
        );
    }
    protected function generateJournalSequence(): string {
        $lastNumber = Journal::query()->max('journal_sequence');

        return ($lastNumber ?? 0) + 1;
    }
}
