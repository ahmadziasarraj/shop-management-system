<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function settle(
        Payment $payment,
        object $document,
        float $paymentAmount,
        float $documentAmount,
        float $baseAmount,
    ) {
        return DB::transaction(function () use (
            $payment,
            $document,
            $paymentAmount,
            $documentAmount,
            $baseAmount,
        ) {

            $allocation = $this->allocationService->allocate(
                $payment,
                $document,
                $paymentAmount,
                $documentAmount,
                $baseAmount,
            );

            $this->calculateRealizedFx(
                $allocation
            );

            $this->updateDocumentStatus(
                $document
            );

            return $allocation;
        });
    }
}
