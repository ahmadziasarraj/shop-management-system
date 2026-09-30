<?php

namespace App\Actions\Payments;

use App\Models\Payment;
use App\Services\Payments\AdvancePaymentService;

class AdvancePaymentAction
{
    public function __construct(
        protected AdvancePaymentService $service,
    ) {}

    public function execute(
        Payment $payment,
        float $amount,
    ): Advance {

        return $this->service->create(
            payment: $payment,
            amount: $amount,
        );
    }
}
