<?php

namespace App\Actions\Sales;

use App\Enums\SalesInvoiceStatus;
use App\Models\SaleInvoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PostSalesInvoiceAction
{
    public function __construct() {}

    public function execute(SaleInvoice $invoice): SaleInvoice
    {

        return DB::transaction(function () use ($invoice) {

            $this->validate($invoice);

            // $this->calculateTotals($invoice);

            // $this->issueInventory($invoice);

            // $this->postRevenue($invoice);

            // $this->postReceivableOrCash($invoice);

            // $this->markPosted($invoice);

            return $invoice->fresh();
        });
    }
    private function validate(SaleInvoice $invoice): void
    {
        if ($invoice->status !== SalesInvoiceStatus::Draft) {
            throw new RuntimeException(
                'Only draft invoices can be posted.'
            );
        }

        if ($invoice->lines()->count() === 0) {
            throw new RuntimeException(
                'Invoice must contain at least one item.'
            );
        }



        // Then validate:

        // product is active
        // warehouse is active
        // quantity > 0
        // selling price >= 0
        // customer exists if credit sale
        // currency exists
        // exchange rate exists
        // stock is sufficient
        // tax is valid
        // invoice total is valid


        // $available = $inventory->availableQuantity(
        // warehouseId: $invoice->warehouse_id,
        // product: $line->productVariant,)
        // if ($available < $line->quantity) {
        //     throw new InsufficientStockException(
        //         $line->productVariant,
        //         $line->quantity,
        //         $available
        //     );
        // }

        // $result = $inventory->issue(
        //     companyId: $invoice->company_id,
        //     warehouseId: $invoice->warehouse_id,
        //     locationId: $line->location_id,
        //     product: $line->productVariant,
        //     quantity: $line->quantity,
        //     reference: $invoice,
        // );

        // $line->update([
        //     'cost_unit' => $result['unit_cost'],
        //     'cost_total' => $result['cogs'],
        // ]);


    }
}
