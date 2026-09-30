<?php

namespace App\Actions\Payments;

use App\Models\Currency;
use App\Services\Payments\HawalaSettlementService;

class HawalaExchangeAction
{
    public function __construct(
        protected HawalaSettlementService $service,
    ) {}

    public function execute(
        HawalaOffice $office,
        Currency $fromCurrency,
        float $fromAmount,
        Currency $toCurrency,
        float $toAmount,
        ?float $fee = null,
    ) {

        return $this->service->exchange(
            office: $office,
            fromCurrency: $fromCurrency,
            fromAmount: $fromAmount,
            toCurrency: $toCurrency,
            toAmount: $toAmount,
            fee: $fee,
        );
    }
}
