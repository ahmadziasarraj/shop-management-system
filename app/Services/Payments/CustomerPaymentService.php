<?php

namespace App\Services\Payments;

use App\Actions\Accounting\PostJournalAction;
use App\Data\Accounting\JournalData;
use App\Data\Accounting\JournalLineData;
use App\Enums\PartyLedgerEntryType;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Party;
use App\Models\Payment;
use App\Models\SaleInvoice;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PartyLedgerService;
use App\Services\Accounting\PaymentAllocationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

class CustomerPaymentService
{
    public function __construct(
        protected AdvancePaymentService $advanceService,
        protected AccountingService $accounting,
        protected PostJournalAction $postJournal,
        protected PartyLedgerService $ledgerService,
        protected PaymentService $paymentService

    ) {}

    public function invoicePayment(SaleInvoice $invoice)
    {

        return DB::transaction(function () use ($invoice) {
            $invoice->loadMissing(['party', 'hawalaOffice']);

            // First Debit Inventory and Credit AccountsPayable
            $this->postInvoicePayment($invoice);

            // Create Payment for the invoice['amount_paid]
            $payment = $this->paymentService->saleInvoicePayment($invoice);

            $settlementAccount = match ($invoice->payment_method) {
                PaymentMethod::Cash   => $this->account('1110'),
                PaymentMethod::HawalaOffice => $this->account('1120'),
                PaymentMethod::Bank   => $this->account('1150'),
                default  => throw new LogicException('Invalid payment method.'),
            };

            $this->postCustomerPayment(
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
        SaleInvoice $invoice,
    ): void {
        $accountsReceivable = $this->account('1130');
        $salesRevenue = $this->account('4100');
        $costOfGoodsSold = $this->account('5100');
        $inventory = $this->account('1140');

        $totalAmount = abs($invoice->total_amount);
        $totalBaseAmount = abs($invoice->base_total_amount);

        // Total cost of the inventory sold.
        $totalCost = $invoice->lines->sum('cogs_total');

        $lines = collect([
            // Customer owes us money.
            new JournalLineData(
                account: $accountsReceivable,
                currency: $invoice->currency,
                debit: $totalAmount,
                credit: 0,
                exchangeRate: $invoice->exchange_rate,
            ),

            // We earned revenue.
            new JournalLineData(
                account: $salesRevenue,
                currency: $invoice->currency,
                debit: 0,
                credit: $totalAmount,
                exchangeRate: $invoice->exchange_rate,
            ),

            // Cost of inventory sold.
            new JournalLineData(
                account: $costOfGoodsSold,
                currency: $invoice->currency,
                debit: $totalCost,
                credit: 0,
                exchangeRate: $invoice->exchange_rate,
            ),

            // Inventory leaves the business.
            new JournalLineData(
                account: $inventory,
                currency: $invoice->currency,
                debit: 0,
                credit: $totalCost,
                exchangeRate: $invoice->exchange_rate,
            ),
        ]);

        $journalData = new JournalData(
            journalDate: now()->toDateString(),
            lines: $lines,
            description: sprintf(
                'Sale invoice #%s.',
                $invoice->system_invoice_number
            ),
            referenceType: SaleInvoice::class,
            referenceId: (string) $invoice->id,
            createdBy: Auth::id(),
        );

        $journal = $this->accounting->createJournal($journalData);
        $this->postJournal->execute($journal);

        // Customer ledger.
        // Sale increases what the customer owes us.
        $this->ledgerService->record(
            party: $invoice->party,
            currency: $invoice->currency,
            entryType: PartyLedgerEntryType::Sale->value,
            debit: $totalAmount,
            credit: 0,
            baseDebit: $totalBaseAmount,
            baseCredit: 0,
            exchangeRate: $invoice->exchange_rate,
            description: sprintf(
                'Sale invoice #%s.',
                $invoice->system_invoice_number
            ),
            reference: $invoice,
        );
    }

    private function postCustomerPayment(
        SaleInvoice $invoice,
        Account $settlementAccount,
        Payment $payment
    ): void {
        $accountsReceivable = $this->account('1130');

        $lines = collect([

            new JournalLineData(
                account: $settlementAccount,
                currency: $invoice->currency,
                debit: abs($payment->amount),
                credit: 0,
                exchangeRate: $invoice->exchange_rate,
            ),
            new JournalLineData(
                account: $accountsReceivable,
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
                'Customer payment against sale invoice #%s.',
                $invoice->system_invoice_number
            ),
            referenceType: SaleInvoice::class,
            referenceId: (string) $invoice->id,
            createdBy: Auth::id(),
        );

        $journal = $this->accounting->createJournal($journalData);

        $this->postJournal->execute($journal);

        // Customer Ledger
        $this->ledgerService->record(
            party: $invoice->party,
            currency: $invoice->currency,
            entryType: PartyLedgerEntryType::CustomerPayment->value,
            debit: 0,
            credit: abs($payment->amount),
            baseDebit: 0,
            baseCredit: abs($payment->base_amount),
            exchangeRate: $invoice->exchange_rate,
            description: sprintf(
                'Payment #%s against Sale invoice #%s.',
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
                debit: abs($payment->amount),
                credit: 0,
                baseDebit: abs($payment->base_amount),
                baseCredit: 0,
                exchangeRate: $invoice->exchange_rate,
                description: sprintf(
                    'Hawala payment to: %s against Sale invoice #%s. Payment#%s',
                    $invoice->party->name,
                    $invoice->system_invoice_number,
                    $payment->payment_number,
                ),
                reference: $payment,
            );
        }
    }
}
