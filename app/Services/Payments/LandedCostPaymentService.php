<?php

namespace App\Services\Payments;

use App\Actions\Accounting\PostJournalAction;
use App\Data\Accounting\JournalData;
use App\Data\Accounting\JournalLineData;
use App\Enums\PartyLedgerEntryType;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\LandedCost;
use App\Models\Payment;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PartyLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

class LandedCostPaymentService
{
    public function __construct(
        protected AdvancePaymentService $advanceService,
        protected AccountingService $accounting,
        protected PostJournalAction $postJournal,
        protected PartyLedgerService $ledgerService,
        protected PaymentService $paymentService

    ) {}

    public function landedCostPayment(LandedCost $landedCost)
    {

        return DB::transaction(function () use ($landedCost) {
            $landedCost->loadMissing(['party', 'hawalaOffice']);

            // First Debit Inventory and Credit AccountsPayable
            $this->postLandedCostPayment($landedCost);

            // Create Payment for the landedCost['amount]
            $payment = $this->paymentService->landedCostPayment($landedCost);

            $settlementAccount = match ($landedCost->payment_method) {
                PaymentMethod::Cash   => $this->account('1110'),
                PaymentMethod::HawalaOffice => $this->account('1120'),
                PaymentMethod::Bank   => $this->account('1150'),
                default  => throw new LogicException('Invalid payment method.'),
            };

            $this->postPartyPayment(
                landedCost: $landedCost,
                settlementAccount: $settlementAccount,
                payment: $payment
            );
        });
    }

    private function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    private function postLandedCostPayment(
        LandedCost $landedCost,
    ): void {
        $accountsPayable = $this->account('2110'); // AcountsPayable
        $settlementAccount = $this->account('1140'); // Inventory

        $lines = collect([
            new JournalLineData(
                account: $settlementAccount,
                currency: $landedCost->currency,
                debit: abs($landedCost->amount),
                credit: 0,
                exchangeRate: $landedCost->exchange_rate,
            ),

            new JournalLineData(
                account: $accountsPayable,
                currency: $landedCost->currency,
                debit: 0,
                credit: abs($landedCost->amount),
                exchangeRate: $landedCost->exchange_rate,
            )
        ]);
        $journalData = new JournalData(
            journalDate: now()->toDateString(),
            lines: $lines,
            description: sprintf(
                'Landed Cost to %s. Purchase invoice #%s.',
                $landedCost->party->name,
                $landedCost->purchaseInvoice->system_invoice_number
            ),
            referenceType: LandedCost::class,
            referenceId: (string) $landedCost->id,
            createdBy: Auth::id()
        );
        $journal = $this->accounting->createJournal($journalData);
        $this->postJournal->execute($journal);

        // Create Ledger For Party
        $this->ledgerService->record(
            party: $landedCost->party,
            currency: $landedCost->currency,
            entryType: PartyLedgerEntryType::LandedCostPayment->value,
            debit: 0,
            credit: $landedCost->amount,
            baseDebit: 0,
            baseCredit: $landedCost->base_amount,
            exchangeRate: $landedCost->exchange_rate,
            description: sprintf(
                'Purchase invoice #%s. Reason: %s',
                $landedCost->purchaseInvoice->system_invoice_number,
                $landedCost->description,
            ),
        );
    }

    private function postPartyPayment(
        LandedCost $landedCost,
        Account $settlementAccount,
        Payment $payment
    ): void {
        $accountsPayable = Account::where('code', '2110')->firstOrFail();

        $lines = collect([
            new JournalLineData(
                account: $accountsPayable,
                currency: $landedCost->currency,
                debit: abs($payment->amount),
                credit: 0,
                exchangeRate: $landedCost->exchange_rate,
            ),

            new JournalLineData(
                account: $settlementAccount,
                currency: $landedCost->currency,
                debit: 0,
                credit: abs($payment->amount),
                exchangeRate: $landedCost->exchange_rate,
            ),
        ]);

        $journalData = new JournalData(
            journalDate: now()->toDateString(),
            lines: $lines,
            description: sprintf(
                'Laded Cost Payment. Reason: %s. Purchase invoice #%s. ',
                $landedCost->description,
                $landedCost->purchaseInvoice->system_invoice_number,
            ),
            referenceType: LandedCost::class,
            referenceId: (string) $landedCost->id,
            createdBy: Auth::id(),
        );

        $journal = $this->accounting->createJournal($journalData);

        $this->postJournal->execute($journal);

        // Party Ledger
        $this->ledgerService->record(
            party: $landedCost->party,
            currency: $landedCost->currency,
            entryType: PartyLedgerEntryType::LandedCostPayment->value,
            debit: abs($payment->amount),
            credit: 0,
            baseDebit: abs($payment->base_amount),
            baseCredit: 0,
            exchangeRate: $landedCost->exchange_rate,
            description: sprintf(
                'Payment #%s  against purchase invoice #%s. Reason: %s.',
                $payment->payment_number,
                $landedCost->purchaseInvoice->system_invoice_number,
                $landedCost->description
            ),
            reference: $payment,
        );

        // Hawala Office Ledger
        if ($landedCost->payment_method == PaymentMethod::HawalaOffice) {
            $this->ledgerService->record(
                party: $landedCost->hawalaOffice,
                currency: $landedCost->currency,
                entryType: PartyLedgerEntryType::HawalaPayment->value,
                debit: 0,
                credit: abs($payment->amount),
                baseDebit: 0,
                baseCredit: abs($payment->base_amount),
                exchangeRate: $landedCost->exchange_rate,
                description: sprintf(
                    'Hawala payment to: %s against purchase invoice #%s. Payment#%s. Reason: %s.',
                    $landedCost->party->name,
                    $landedCost->purchaseInvoice->system_invoice_number,
                    $payment->payment_number,
                    $landedCost->description
                ),
                reference: $payment,
            );
        }
    }
}
