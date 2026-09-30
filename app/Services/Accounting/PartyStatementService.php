<?php

namespace App\Services\Accounting;

use App\Models\Party;
use App\Models\Currency;
use Illuminate\Support\Collection;

class PartyStatementService
{
    public function entries(
        Party $party,
        ?Currency $currency = null,
        $from = null,
        $to = null,
    ) {
        return $party->ledgerEntries()
            ->with([
                'currency',
                'reference',
            ])
            ->when(
                $currency,
                fn($query) =>
                $query->where(
                    'currency_id',
                    $currency->id
                )
            )
            ->when(
                $from,
                fn($query) =>
                $query->whereDate(
                    'transaction_date',
                    '>=',
                    $from
                )
            )
            ->when(
                $to,
                fn($query) =>
                $query->whereDate(
                    'transaction_date',
                    '<=',
                    $to
                )
            )
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function openingBalance(
        Party $party,
        Currency $currency,
        $from
    ): array {

        $entries = $party->ledgerEntries()
            ->where('currency_id', $currency->id)
            ->whereDate('transaction_date', '<', $from)
            ->get();

        return [
            'debit' => $entries->sum('debit_amount'),
            'credit' => $entries->sum('credit_amount'),
        ];
        //     Running balance // For each currency:
        // $balance = $openingDebit - $openingCredit;
        // foreach ($entries as $entry) {
        //     $balance += $entry->debit_amount;
        //     $balance -= $entry->credit_amount;
        //     $entry->running_balance = $balance;
        // }
    }

    public function currencySummary(Party $party): Collection
    {
        return $party->ledgerEntries()
            ->with('currency')
            ->get()
            ->groupBy('currency_id')
            ->map(function ($entries) {

                return [
                    'currency' => $entries->first()->currency,
                    'debit' => $entries->sum('debit_amount'),
                    'credit' => $entries->sum('credit_amount'),
                    'balance' => $entries->sum('debit_amount') - $entries->sum('credit_amount'),
                ];
            });
    }
}
