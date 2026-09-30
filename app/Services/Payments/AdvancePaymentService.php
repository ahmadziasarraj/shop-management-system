<?php

namespace App\Services\Payments;

use App\Models\Payment;

class AdvancePaymentService
{
    public function create(
        Payment $payment,
        float $amount,
    ) {
        return Advance::create([
            'company_id' => $payment->company_id,

            'party_id' => $payment->party_id,

            'payment_id' => $payment->id,

            'currency_id' => $payment->currency_id,

            'amount' => $amount,

            'remaining_amount' => $amount,

            'base_amount' => $this->calculateBaseAmount(
                $payment,
                $amount
            ),
        ]);
    }
}
