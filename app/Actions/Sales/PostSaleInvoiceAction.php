<?php

namespace App\Actions\Sales;

use App\Actions\Payments\CustomerPaymentAction;
use App\Models\SaleInvoice;
use App\Services\Inventory\StockItemConsumeService;
use App\Services\Inventory\WeightedAverageCostService;
use Illuminate\Support\Facades\DB;

final class PostSaleInvoiceAction
{
    public function __construct(
        private readonly WeightedAverageCostService $costService,
        private readonly StockItemConsumeService $stockConsume,
        private readonly CustomerPaymentAction $customerPayment,
    ) {
    }

    public function execute(SaleInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {

            // Consume the stock items for this sale invoice line.
            foreach ($invoice->lines as $line) { 
                // This is for Stock Balance.
                $result = $this->costService->issue(
                    product: $line->productVariant,
                    quantity: $line->quantity,
                );

                foreach ($line['cogs_justification']['stock_items'] as $key => $item) {
                    $this->stockConsume->consume(
                        stockId: $item['stock_item_id'],
                        quantity: (float) $item['taken'],
                    );
                }
            }
            // Update the invoice status to Posted
            $invoice->status = \App\Enums\SalesInvoiceStatus::Posted;
            $invoice->save();

            $this->customerPayment->saleInvoicePayment(
                invoice: $invoice
            );
        });
    }
}
