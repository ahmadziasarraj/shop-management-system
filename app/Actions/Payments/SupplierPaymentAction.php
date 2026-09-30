<?php

namespace App\Actions\Payments;

use App\Models\LandedCost;
use App\Models\PurchaseInvoice;
use App\Services\Payments\LandedCostPaymentService;
use App\Services\Payments\SupplierPaymentService;
use Illuminate\Support\Collection;

class SupplierPaymentAction
{
    public function __construct(
        protected SupplierPaymentService $service,
        protected LandedCostPaymentService $landedCostPaymentService,
    ) {}

     public function purchaseInvoicePayment(
        PurchaseInvoice $invoice,
        Collection $landedCosts
    ): void {
        $this->service->invoicePayment(invoice: $invoice);
        $landedCosts->each(
            fn(LandedCost $landedCost) => $this->landedCostPaymentService->landedCostPayment($landedCost)
        );
    }

    // public function execute(
    //     Party $supplier,
    //     Currency $currency,
    //     float $amount,
    //     string $paymentMethod,
    //     ?array $allocations = null,
    // ): Payment {

    //     return $this->service->pay(
    //         supplier: $supplier,
    //         currency: $currency,
    //         amount: $amount,
    //         paymentMethod: $paymentMethod,
    //         allocations: $allocations,
    //     );
    // }
}
