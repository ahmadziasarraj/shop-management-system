<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentAllocationService
{
    public function allocate(
        Payment $payment,
        object $document,
        float $paymentAmount,
        float $documentAmount,
        float $baseAmount,
        ?float $exchangeRate = null,
    ): PaymentAllocation {

        return DB::transaction(function () use (
            $payment,
            $document,
            $paymentAmount,
            $documentAmount,
            $baseAmount,
            $exchangeRate,
        ) {

            $remaining = $this->remainingPayment($payment);

            if ($paymentAmount > $remaining) {
                throw new RuntimeException(
                    'Payment allocation exceeds remaining payment amount.'
                );
            }

            return PaymentAllocation::create([
                'payment_id' => $payment->id,
                'document_type' => $document::class,
                'document_id' => $document->id,
                'amount' => $paymentAmount,
                'document_amount' => $documentAmount,
                'base_amount' => $baseAmount,
                'exchange_rate' => $exchangeRate,
            ]);
        });
    }

    public function remainingPayment(Payment $payment): float
    {
        return $payment->amount - $payment->allocations()->sum('amount');
    }
}
