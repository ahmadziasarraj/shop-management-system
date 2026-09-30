<?php

namespace App\Services\Payments;

use App\Models\Currency;
use App\Models\Party;
use App\Models\Payment;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PartyLedgerService;
use App\Services\Accounting\PaymentAllocationService;
use Illuminate\Support\Facades\DB;

class CustomerPaymentService
{
    public function __construct(
        protected PaymentAllocationService $allocationService,
        protected AdvancePaymentService $advanceService,
        protected AccountingService $accountingService,
        protected PartyLedgerService $partyLedgerService,
    ) {}

    public function receive(
        Party $customer,
        Currency $currency,
        float $amount,
        string $paymentMethod,
        ?array $allocations = null,
    ): Payment {

        return DB::transaction(function () use (
            $customer,
            $currency,
            $amount,
            $paymentMethod,
            $allocations,
        ) {

            $payment = Payment::create([
                'party_id' => $customer->id,
                'currency_id' => $currency->id,

                'payment_number' => PaymentNumberService::next(),

                'direction' => 'in',
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'payment_date' =>now(),
            ]);

            /*
            * Accounting:
            *
            * Dr Cash/Hawala
            * Cr Accounts Receivable
            */
            $this->accountingService->recordCustomerPayment($payment);

            /*
            * Party subledger:
            *
            * Credit customer account
            */
            $this->partyLedgerService->recordCustomerPayment($payment);

            if ($allocations) {

                foreach ($allocations as $allocation) {

                    $this->allocationService->allocate(
                        $payment,
                        $allocation['document'],
                        $allocation['payment_amount'],
                        $allocation['document_amount'],
                        $allocation['base_amount'],
                        $allocation['exchange_rate'] ?? null,
                    );
                }
            }

            return $payment;
        });
    }
}
