<?php

namespace App\Services\Payments;

use App\Actions\Accounting\PostJournalAction;
use App\Data\Accounting\JournalData;
use App\Data\Accounting\JournalLineData;
use App\Enums\PartyLedgerEntryType;
use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\Payment;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PartyLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

class HawalaSettlementService
{
    public function __construct(
        protected AdvancePaymentService $advanceService,
        protected AccountingService $accounting,
        protected PostJournalAction $postJournal,
        protected PartyLedgerService $ledgerService,

    ) {}

    private const CASH_ACCOUNT = '1110';
    private const BANK_ACCOUNT = '1150';
    private const HAWALA_ACCOUNT = '1120';

    private const HAWALA_SETTLEMENT_ACCOUNT = self::HAWALA_ACCOUNT;
    private const HAWALA_RECEIVABLE_ACCOUNT = self::HAWALA_ACCOUNT;
    private const HAWALA_PAYABLE_ACCOUNT = self::HAWALA_ACCOUNT;

    /**
     * Receive money from a Hawala Office.
     *
     * Dr Cash / Bank / Settlement
     * Cr Hawala Office Receivable
     */
    public function receiveMoney(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {

            $settlementAccount = $this->getSettlementAccount($payment);
            $receivableAccount = $this->account(self::HAWALA_RECEIVABLE_ACCOUNT);

            $this->postJournal(
                payment: $payment,
                debitAccount: $settlementAccount,
                creditAccount: $receivableAccount,
                description: sprintf(
                    'Hawala Office payment received from %s. Payment #%s.',
                    $payment->party->name,
                    $payment->payment_number,
                ),
            );

            $this->recordReceiptLedgers($payment);
        });
    }

    /**
     * Pay money to a Hawala Office.
     *
     * Dr Hawala Office Payable
     * Cr Cash / Bank / Settlement
     */
    public function payMoney(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {

            $settlementAccount = $this->getSettlementAccount($payment);
            $payableAccount = $this->account(self::HAWALA_PAYABLE_ACCOUNT);

            $this->postJournal(
                payment: $payment,
                debitAccount: $payableAccount,
                creditAccount: $settlementAccount,
                description: sprintf(
                    'Payment to Hawala Office for %s. Payment #%s.',
                    $payment->party->name,
                    $payment->payment_number,
                ),
            );

            $this->recordPaymentLedgers($payment);
        });
    }

    /**
     * Get the account through which money is received or paid.
     */
    private function getSettlementAccount(Payment $payment): Account
    {
        $code = match ($payment->payment_method) {
            PaymentMethod::Cash => self::CASH_ACCOUNT,
            PaymentMethod::Bank => self::BANK_ACCOUNT,
            PaymentMethod::HawalaOffice => self::HAWALA_SETTLEMENT_ACCOUNT,

            default => throw new LogicException(
                'Unsupported payment method for Hawala settlement.'
            ),
        };

        return $this->account($code);
    }

    /**
     * Create and post a balanced journal.
     *
     * The debit and credit accounts must be different accounts.
     */
    private function postJournal(
        Payment $payment,
        Account $debitAccount,
        Account $creditAccount,
        string $description,
    ): void {
        // if ($debitAccount->is($creditAccount)) {
        //     throw new LogicException(
        //         'Debit and credit accounts must be different.'
        //     );
        // }

        $amount = abs((float) $payment->amount);

        $lines = collect([
            new JournalLineData(
                account: $debitAccount,
                currency: $payment->currency,
                debit: $amount,
                credit: 0,
                exchangeRate: $payment->exchange_rate,
            ),

            new JournalLineData(
                account: $creditAccount,
                currency: $payment->currency,
                debit: 0,
                credit: $amount,
                exchangeRate: $payment->exchange_rate,
            ),
        ]);

        $journalData = new JournalData(
            journalDate: now()->toDateString(),
            lines: $lines,
            description: $description,
            referenceType: Payment::class,
            referenceId: (string) $payment->id,
            createdBy: Auth::id(),
        );

        $journal = $this->accounting->createJournal($journalData);

        $this->postJournal->execute($journal);
    }

    /**
     * Record party ledger entries when money is received.
     */
    private function recordReceiptLedgers(Payment $payment): void
    {
        $amount = abs((float) $payment->amount);
        $baseAmount = abs((float) $payment->base_amount);

        $description = sprintf(
            'Hawala payment received. Payment #%s. Narration: %s',
            $payment->payment_number,
            $payment->notes ?? '',
        );

        if ($payment->payment_method === PaymentMethod::HawalaOffice) {
            $hawalaOffice = $payment->hawalaOffice;

            if (!$hawalaOffice) {
                throw new LogicException(
                    'Hawala Office is required for this payment method.'
                );
            }

            $this->ledgerService->record(
                party: $hawalaOffice,
                currency: $payment->currency,
                entryType: PartyLedgerEntryType::HawalaPayment->value,
                debit: $amount,
                credit: 0,
                baseDebit: $baseAmount,
                baseCredit: 0,
                exchangeRate: $payment->exchange_rate,
                description: sprintf(
                    'Payment received from Hawala Office %s. Payment #%s.',
                    $payment->party->name,
                    $payment->payment_number,
                ),
                reference: $payment,
            );

            $description = sprintf(
                'Payment received via Hawala Office (%s). Payment #%s. Narration: %s',
                $hawalaOffice->name,
                $payment->payment_number,
                $payment->notes ?? '',
            );
        }

        $this->ledgerService->record(
            party: $payment->party,
            currency: $payment->currency,
            entryType: PartyLedgerEntryType::CustomerPayment->value,
            debit: 0,
            credit: $amount,
            baseDebit: 0,
            baseCredit: $baseAmount,
            exchangeRate: $payment->exchange_rate,
            description: $description,
            reference: $payment,
        );
    }

    /**
     * Record party ledger entries when money is paid.
     */
    private function recordPaymentLedgers(Payment $payment): void
    {
        $amount = abs((float) $payment->amount);
        $baseAmount = abs((float) $payment->base_amount);

        $description = sprintf(
            'Payment to Hawala Office. Payment #%s. Narration: %s',
            $payment->payment_number,
            $payment->notes ?? '',
        );

        if ($payment->payment_method === PaymentMethod::HawalaOffice) {
            $hawalaOffice = $payment->hawalaOffice;

            if (!$hawalaOffice) {
                throw new LogicException(
                    'Hawala Office is required for this payment method.'
                );
            }

            $this->ledgerService->record(
                party: $hawalaOffice,
                currency: $payment->currency,
                entryType: PartyLedgerEntryType::HawalaPayment->value,
                debit: 0,
                credit: $amount,
                baseDebit: 0,
                baseCredit: $baseAmount,
                exchangeRate: $payment->exchange_rate,
                description: sprintf(
                    'Payment to Hawala Office for customer %s. Payment #%s.',
                    $payment->party->name,
                    $payment->payment_number,
                ),
                reference: $payment,
            );

            $description = sprintf(
                'Payment to Hawala Office (%s). Payment #%s. Narration: %s',
                $hawalaOffice->name,
                $payment->payment_number,
                $payment->notes ?? '',
            );
        }

        $this->ledgerService->record(
            party: $payment->party,
            currency: $payment->currency,
            entryType: PartyLedgerEntryType::CustomerPayment->value,
            debit: $amount,
            credit: 0,
            baseDebit: $baseAmount,
            baseCredit: 0,
            exchangeRate: $payment->exchange_rate,
            description: $description,
            reference: $payment,
        );
    }

    private function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }
}
