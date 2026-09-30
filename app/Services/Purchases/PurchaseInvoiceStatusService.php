<?php

namespace App\Services\Purchases;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\PurchaseInvoice;

final class PurchaseInvoiceStatusService
{
    public function update(PurchaseInvoice $invoice): void
    {

        $invoice->load('lines');

        $total = $invoice->lines->sum(fn($line) => (float) $line->quantity);

        $received = $invoice->lines->sum(
            fn($line) => (float) $line->received_quantity
        );

        if ($received <= 0) {
            $status = PurchaseInvoiceStatus::Confirmed;
        } elseif ($received < $total) {
            $status = PurchaseInvoiceStatus::PartiallyReceived;
        } else {
            $status = PurchaseInvoiceStatus::FullyReceived;
        }

        $invoice->update([
            'status' => $status,
        ]);
    }
}
