<?php

namespace App\Actions\Payments;

use App\Models\Currency;
use App\Models\Party;
use App\Models\Payment;
use App\Models\SaleInvoice;
use App\Services\Payments\CustomerPaymentService;

class CustomerPaymentAction
{
    public function __construct(
        protected CustomerPaymentService $paymentService,
    ) {}

    public function saleInvoicePayment(SaleInvoice $invoice) : void {
        $this->paymentService->invoicePayment(invoice: $invoice);
    }
}
