<?php

namespace App\Services\Payments;

use App\Actions\Accounting\PostJournalAction;
use App\Data\Accounting\JournalData;
use App\Data\Accounting\JournalLineData;
use App\Enums\PartyLedgerEntryType;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PartyLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

class SupplierPaymentService
{
    public function __construct(
        protected AdvancePaymentService $advanceService,
        protected AccountingService $accounting,
        protected PostJournalAction $postJournal,
        protected PartyLedgerService $ledgerService,
        protected PaymentService $paymentService

    ) {}

    public function invoicePayment(PurchaseInvoice $invoice) {

        return DB::transaction(function () use ($invoice) {
            $invoice->loadMissing(['party', 'hawalaOffice']);

            // First Debit Inventory and Credit AccountsPayable
            $this->postInvoicePayment($invoice);

            // Create Payment for the invoice['amount_paid]
            $payment = $this->paymentService->purchaseInvoicePayment($invoice);

            $settlementAccount = match ($invoice->payment_method) {
                PaymentMethod::Cash   => $this->account('1110'),
                PaymentMethod::HawalaOffice => $this->account('1120'),
                PaymentMethod::Bank   => $this->account('1150'),
                default  => throw new LogicException('Invalid payment method.'),
            };

            $this->postSupplierPayment(
                invoice: $invoice,
                settlementAccount: $settlementAccount,
                payment: $payment
            );
        });
    }

    private function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    private function postInvoicePayment(
        PurchaseInvoice $invoice,
    ): void {
        $accountsPayable = $this->account('2110');
        $settlementAccount = $this->account('1140');

        $lines = collect([
            new JournalLineData(
                account: $settlementAccount,
                currency: $invoice->currency,
                debit: abs($invoice->total_amount),
                credit: 0,
                exchangeRate: $invoice->exchange_rate,
            ),

            new JournalLineData(
                account: $accountsPayable,
                currency: $invoice->currency,
                debit: 0,
                credit: abs($invoice->total_amount),
                exchangeRate: $invoice->exchange_rate,
            )
        ]);
        $journalData = new JournalData(
            journalDate: now()->toDateString(),
            lines: $lines,
            description: sprintf('Payment against purchase invoice #%s.', $invoice->system_invoice_number),
            referenceType: PurchaseInvoice::class,
            referenceId: (string) $invoice->id,
            createdBy: Auth::id()
        );
        $journal = $this->accounting->createJournal($journalData);
        $this->postJournal->execute($journal);

        // Create Ledger For Supplier
        $this->ledgerService->record(
            party: $invoice->party,
            currency: $invoice->currency,
            entryType: PartyLedgerEntryType::Purchase->value,
            debit: 0,
            credit: $invoice->total_amount,
            baseDebit: 0,
            baseCredit: $invoice->base_total_amount,
            exchangeRate: $invoice->exchange_rate,
            description: sprintf('Purchase invoice #%s.', $invoice->system_invoice_number),
            reference: $invoice
        );
    }

    private function postSupplierPayment(
        PurchaseInvoice $invoice,
        Account $settlementAccount,
        Payment $payment
    ): void {
        $accountsPayable = Account::where('code', '2110')->firstOrFail();

        $lines = collect([
            new JournalLineData(
                account: $accountsPayable,
                currency: $invoice->currency,
                debit: abs($payment->amount),
                credit: 0,
                exchangeRate: $invoice->exchange_rate,
            ),

            new JournalLineData(
                account: $settlementAccount,
                currency: $invoice->currency,
                debit: 0,
                credit: abs($payment->amount),
                exchangeRate: $invoice->exchange_rate,
            ),
        ]);

        $journalData = new JournalData(
            journalDate: now()->toDateString(),
            lines: $lines,
            description: sprintf(
                'Supplier payment against purchase invoice #%s.',
                $invoice->system_invoice_number
            ),
            referenceType: PurchaseInvoice::class,
            referenceId: (string) $invoice->id,
            createdBy: Auth::id(),
        );

        $journal = $this->accounting->createJournal($journalData);

        $this->postJournal->execute($journal);

        // Supplier Ledger
        $this->ledgerService->record(
            party: $invoice->party,
            currency: $invoice->currency,
            entryType: PartyLedgerEntryType::SupplierPayment->value,
            debit: abs($payment->amount),
            credit: 0,
            baseDebit: abs($payment->base_amount),
            baseCredit: 0,
            exchangeRate: $invoice->exchange_rate,
            description: sprintf(
                'Payment #%s against purchase invoice #%s.',
                $payment->payment_number,
                $invoice->system_invoice_number
            ),
            reference: $payment,
        );

        // Hawala Office Ledger
        if ($invoice->payment_method == PaymentMethod::HawalaOffice) {
            $this->ledgerService->record(
                party: $invoice->hawalaOffice,
                currency: $invoice->currency,
                entryType: PartyLedgerEntryType::HawalaPayment->value,
                debit: 0,
                credit: abs($payment->amount),
                baseDebit: 0,
                baseCredit: abs($payment->base_amount),
                exchangeRate: $invoice->exchange_rate,
                description: sprintf(
                    'Hawala payment to: %s against purchase invoice #%s. Payment#%s',
                    $invoice->party->name,
                    $invoice->system_invoice_number,
                    $payment->payment_number,
                ),
                reference: $payment,
            );
        }
    }
}
