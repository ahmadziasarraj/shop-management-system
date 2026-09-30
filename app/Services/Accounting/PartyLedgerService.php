<?php

namespace App\Services\Accounting;

use App\Models\Party;
use App\Models\PartyLedgerEntry;
use App\Models\Currency;

class PartyLedgerService
{
    public function record(
        Party $party,
        Currency $currency,
        string $entryType,
        float $debit,
        float $credit,
        float $baseDebit,
        float $baseCredit,
        ?float $exchangeRate = null,
        ?string $description = null,
        ?object $reference = null,
    ): PartyLedgerEntry {

        return PartyLedgerEntry::create([
            'party_id' => $party->id,
            'currency_id' => $currency->id,
            'reference_type' => $reference
                ? $reference::class
                : 'manual',

            'reference_id' => $reference?->id ?? 0,

            'type' => $entryType,
            'debit_amount' => $debit,
            'credit_amount' => $credit,
            'base_debit_amount' => $baseDebit,
            'base_credit_amount' => $baseCredit,
            'exchange_rate' => $exchangeRate,
            'transaction_date' => now(),
            'description' => $description,
        ]);
    }
}
