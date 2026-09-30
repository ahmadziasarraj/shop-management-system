<?php

namespace App\Actions\Resources;

use App\Actions\Accounting\CreateOpeningBalanceAction;
use App\Actions\Accounting\PostJournalAction;
use App\Data\Accounting\JournalData;
use App\Data\Accounting\JournalLineData;
use App\Enums\PartyLedgerEntryType;
use App\Enums\PartyType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Party;
use App\Services\Accounting\ExchangeRateService;
use App\Services\Accounting\PartyLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PartyActions
{
    public function __construct(
        private CreateOpeningBalanceAction $createOpeningBalance,
        private PartyLedgerService $ledgerService,
        private ExchangeRateService $exchangeRate,
        private PostJournalAction $postJournal
    ) {}
    public function create(array $data): Party
    {
        $data['name'] = Str::title(trim($data['name']));

        return DB::transaction(function () use ($data) {
            $party = Party::create($data);
            $openingPayableAmount = (float) array_key_exists('opening_payable', $data) ? $data['opening_payable'] : 0;
            $openingReceivableAmount = (float) array_key_exists('opening_receivable', $data) ? $data['opening_receivable'] : 0;
            $openingBalance = (float) array_key_exists('opening_balance', $data) ? $data['opening_balance'] : 0;
            // dd($openingPayableAmount);

            if (
                $openingPayableAmount !== 0.0 ||
                $openingReceivableAmount !== 0.0 ||
                $openingBalance !== 0.0
            ) {

                $currency = Currency::where('id', $data['currency_id'])->firstOrFail();
                $baseCurrency = Currency::where('is_base', true)->firstOrFail();
                $rate = $this->exchangeRate->getRate(
                    fromCurrencyCode: $baseCurrency->code,
                    toCurrencyCode: $currency->code,
                );

                $balanceAccountResolver = $this->resolveOpeningBalance($data['type'], $openingPayableAmount, $openingReceivableAmount, $openingBalance, $rate);

                if ($data['type'] == PartyType::VendorCustomer) {

                    // For Customer
                    $customerLines = collect();
                    if ($openingReceivableAmount !== 0.0) {
                        $customerLines = collect([
                            new JournalLineData(
                                account: $balanceAccountResolver['customer']['debit']['account'],
                                currency: $currency,
                                debit: $balanceAccountResolver['customer']['debit']['amount'],
                                credit: 0,
                                exchangeRate: $rate
                            ),
                            new JournalLineData(
                                account: $balanceAccountResolver['customer']['credit']['account'],
                                currency: $currency,
                                debit: 0,
                                credit: $balanceAccountResolver['customer']['credit']['amount'],
                                exchangeRate: $rate
                            )
                        ]);

                        $this->ledgerService->record(
                            party: $party,
                            currency: $currency,
                            entryType: PartyLedgerEntryType::OpeningBalance->value,
                            debit: $balanceAccountResolver['customer']['debit']['ledger_amount'],
                            credit: $balanceAccountResolver['customer']['credit']['ledger_amount'],
                            baseDebit: $balanceAccountResolver['customer']['debit']['ledger_base_amount'],
                            baseCredit: $balanceAccountResolver['customer']['credit']['ledger_base_amount'],
                            exchangeRate: $rate,
                            description: 'Opening balance',
                            reference: $party
                        );
                    }

                    // For Vendor
                    $vendorLines = collect();
                    if ($openingPayableAmount !== 0.0) {
                        $vendorLines = collect([
                            new JournalLineData(
                                account: $balanceAccountResolver['vendor']['debit']['account'],
                                currency: $currency,
                                debit: $balanceAccountResolver['vendor']['debit']['amount'],
                                credit: 0,
                                exchangeRate: $rate
                            ),
                            new JournalLineData(
                                account: $balanceAccountResolver['vendor']['credit']['account'],
                                currency: $currency,
                                debit: 0,
                                credit: $balanceAccountResolver['vendor']['credit']['amount'],
                                exchangeRate: $rate
                            ),
                        ]);

                        $this->ledgerService->record(
                            party: $party,
                            currency: $currency,
                            entryType: PartyLedgerEntryType::OpeningBalance->value,
                            debit: $balanceAccountResolver['vendor']['debit']['ledger_amount'],
                            credit: $balanceAccountResolver['vendor']['credit']['ledger_amount'],
                            baseDebit: $balanceAccountResolver['vendor']['debit']['ledger_base_amount'],
                            baseCredit: $balanceAccountResolver['vendor']['credit']['ledger_base_amount'],
                            exchangeRate: $rate,
                            description: 'Opening balance',
                            reference: $party
                        );
                    }

                    $lines = $customerLines->merge($vendorLines);
                } elseif ($data['type'] == PartyType::HawalaOffice) {

                    $lines = collect([
                        new JournalLineData(
                            account: $balanceAccountResolver['debit']['account'],
                            currency: $currency,
                            debit: $balanceAccountResolver['debit']['amount'],
                            credit: 0,
                            exchangeRate: $rate
                        ),
                        new JournalLineData(
                            account: $balanceAccountResolver['credit']['account'],
                            currency: $currency,
                            debit: 0,
                            credit: $balanceAccountResolver['credit']['amount'],
                            exchangeRate: $rate
                        ),
                    ]);

                    $this->ledgerService->record(
                        party: $party,
                        currency: $currency,
                        entryType: PartyLedgerEntryType::OpeningBalance->value,
                        debit: $balanceAccountResolver['debit']['ledger_amount'],
                        credit: $balanceAccountResolver['credit']['ledger_amount'],
                        baseDebit: $balanceAccountResolver['debit']['ledger_base_amount'],
                        baseCredit: $balanceAccountResolver['credit']['ledger_base_amount'],
                        exchangeRate: $rate,
                        description: 'Opening balance',
                        reference: $party
                    );
                } else {
                    $lines = collect([
                        new JournalLineData(
                            account: $balanceAccountResolver['debit']['account'],
                            currency: $currency,
                            debit: $balanceAccountResolver['debit']['amount'],
                            credit: 0,
                            exchangeRate: $rate
                        ),
                        new JournalLineData(
                            account: $balanceAccountResolver['credit']['account'],
                            currency: $currency,
                            debit: 0,
                            credit: $balanceAccountResolver['credit']['amount'],
                            exchangeRate: $rate
                        ),
                    ]);

                    $this->ledgerService->record(
                        party: $party,
                        currency: $currency,
                        entryType: PartyLedgerEntryType::OpeningBalance->value,
                        debit: $balanceAccountResolver['debit']['ledger_amount'],
                        credit: $balanceAccountResolver['credit']['ledger_amount'],
                        baseDebit: $balanceAccountResolver['debit']['ledger_base_amount'],
                        baseCredit: $balanceAccountResolver['credit']['ledger_base_amount'],
                        exchangeRate: $rate,
                        description: 'Opening balance',
                        reference: $party
                    );
                }

                $journalData = new JournalData(
                    journalDate: now()->toDateString(),
                    lines: $lines,
                    description: sprintf('Opening balance for %s', $party->name),
                    referenceType: Party::class,
                    referenceId: (string) $party->id,
                    createdBy: Auth::user()->id
                );

                $journal = $this->createOpeningBalance->execute($journalData);
                // dd($journal);
                $this->postJournal->execute($journal);
            }
            return $party;
        });
    }

    private function resolveOpeningBalance(
        PartyType $type,
        float $openingPayable,
        float $openingReceivable,
        float $openingBalance,
        float $rate,
    ): array {
        $arAccount = Account::where('code', '1130')->firstOrFail();
        $apAccount = Account::where('code', '2110')->firstOrFail();
        $openingEquityAccount = Account::where('code', '3300')->firstOrFail();
        $hawalaAccount = Account::where('code', '1120')->firstOrFail();

        $absPayableAmount = abs((float) $openingPayable);
        $basePayableAmount = round($absPayableAmount / $rate, 6);
        $absReceivableAmount = abs((float) $openingReceivable);
        $baseReceivableAmount = round($absReceivableAmount / $rate, 6);
        // dd('from resolver',$absPayableAmount);

        return match ($type) {
            PartyType::Customer => $this->getCustomerAccounts($openingReceivable, $openingEquityAccount, $arAccount, $absReceivableAmount, $baseReceivableAmount),

            PartyType::Vendor => $this->getVendorAccounts($openingPayable, $openingEquityAccount, $apAccount, $absPayableAmount, $basePayableAmount),
            PartyType::VendorCustomer => [
                'vendor' => $this->getVendorAccounts($openingPayable, $openingEquityAccount, $apAccount, $absPayableAmount, $basePayableAmount),
                'customer' => $this->getCustomerAccounts($openingReceivable, $openingEquityAccount, $arAccount, $absReceivableAmount, $baseReceivableAmount)
            ],
            PartyType::HawalaOffice => $this->getHawalaAccounts(
                $openingBalance,
                $openingEquityAccount,
                $hawalaAccount,
                $rate
            ),
        };
    }
    private function getHawalaAccounts(
        float $openingBalance,
        Account $openingEquityAccount,
        Account $hawalaAccount,
        float $rate,
    ): array {
        $amount = abs($openingBalance);
        $baseAmount = round($amount / $rate, 6);

        if ($openingBalance > 0) {
            // Hawala owes us
            return [
                'debit' => [
                    'account' => $hawalaAccount,
                    'amount' => $amount,
                    'base_amount' => $baseAmount,
                    'ledger_amount' => $amount,
                    'ledger_base_amount' => $baseAmount,
                ],
                'credit' => [
                    'account' => $openingEquityAccount,
                    'amount' => $amount,
                    'base_amount' => $baseAmount,
                    'ledger_amount' => 0,
                    'ledger_base_amount' => 0,
                ],
            ];
        }

        // We owe Hawala
        return [
            'debit' => [
                'account' => $openingEquityAccount,
                'amount' => $amount,
                'base_amount' => $baseAmount,
                'ledger_amount' => 0,
                'ledger_base_amount' => 0,
            ],
            'credit' => [
                'account' => $hawalaAccount,
                'amount' => $amount,
                'base_amount' => $baseAmount,
                'ledger_amount' => $amount,
                'ledger_base_amount' => $baseAmount,
            ],
        ];
    }


    private function getVendorAccounts($openingPayable, $openingEquityAccount, $apAccount, $absPayableAmount, $basePayableAmount): array
    {
        if ($openingPayable > 0) {
            return [
                'debit' => [
                    'account' => $openingEquityAccount,
                    'amount' => $absPayableAmount,
                    'base_amount' => $basePayableAmount,
                    'ledger_amount' => 0,
                    'ledger_base_amount' => 0

                ],
                'credit' => [
                    'account' => $apAccount,
                    'amount' => $absPayableAmount,
                    'base_amount' => $basePayableAmount,
                    'ledger_amount' => $absPayableAmount,
                    'ledger_base_amount' => $basePayableAmount
                ],
            ];
        }

        return [
            'debit' => [
                'account' => $apAccount,
                'amount' => $absPayableAmount,
                'base_amount' => $basePayableAmount,
                'ledger_amount' => $absPayableAmount,
                'ledger_base_amount' => $basePayableAmount
            ],
            'credit' => [
                'account' => $openingEquityAccount,
                'amount' => $absPayableAmount,
                'base_amount' => $basePayableAmount,
                'ledger_amount' => 0,
                'ledger_base_amount' => 0
            ],
        ];
    }
    private function getCustomerAccounts($openingReceivable, $openingEquityAccount, $arAccount, $absReceivableAmount, $baseReceivableAmount): array
    {
        if ($openingReceivable > 0) {
            return [
                'debit' => [
                    'account' => $arAccount,
                    'amount' => $absReceivableAmount,
                    'base_amount' => $baseReceivableAmount,
                    'ledger_amount' => $absReceivableAmount,
                    'ledger_base_amount' => $baseReceivableAmount
                ],
                'credit' => [
                    'account' => $openingEquityAccount,
                    'amount' => $absReceivableAmount,
                    'base_amount' => $baseReceivableAmount,
                    'ledger_amount' => 0,
                    'ledger_base_amount' => 0
                ],
            ];
        }

        return [
            'debit' => [
                'account' => $openingEquityAccount,
                'amount' => $absReceivableAmount,
                'base_amount' => $baseReceivableAmount,
                'ledger_amount' => 0,
                'ledger_base_amount' => 0
            ],
            'credit' => [
                'account' => $arAccount,
                'amount' => $absReceivableAmount,
                'base_amount' => $baseReceivableAmount,
                'ledger_amount' => $absReceivableAmount,
                'ledger_base_amount' => $baseReceivableAmount
            ],
        ];
    }
}
