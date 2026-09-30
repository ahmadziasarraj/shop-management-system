<?php

namespace App\Actions\Payments;

use App\Models\Currency;
use App\Models\Party;
use App\Models\Payment;
use App\Services\Payments\CustomerPaymentService;

class CustomerPaymentAction
{
    public function __construct(
        protected CustomerPaymentService $paymentService,
    ) {}

    public function execute(
        Party $customer,
        Currency $currency,
        float $amount,
        string $paymentMethod,
        ?array $allocations = null,
    ): Payment {

        return $this->paymentService->receive(
            customer: $customer,
            currency: $currency,
            amount: $amount,
            paymentMethod: $paymentMethod,
            allocations: $allocations,
        );
    }
}
