<?php

namespace App\Actions\Purchases;

use App\Data\Purchases\PurchaseInvoiceData;
use App\Models\PurchaseInvoice;
use App\Services\Purchases\PurchaseInvoiceFinancialSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class EditPurchaseInvoiceAction
{
    public function __construct(
        private readonly PurchaseInvoiceFinancialSummary $financialSummary,
    ) {}

    public function execute(PurchaseInvoice $invoice, PurchaseInvoiceData $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $data) {

            /*
             * ---------------------------------------------------------
             * 1. Calculate each purchase line
             * ---------------------------------------------------------
             */

            $products = collect($data->purchased_products)
                ->map(function (array $product) {

                    $quantity = (float) $product['quantity'];
                    $unitCost = (float) $product['unit_cost'];

                    return [
                        'product_variant_id' => $product['product_variant_id'],
                        'quantity'           => $quantity,
                        'unit_cost'          => $unitCost,
                        'total_cost'    => $quantity * $unitCost,
                    ];
                });

            /*
             * ---------------------------------------------------------
             * 2. Calculate invoice financial summary
             * ---------------------------------------------------------
             */

            $summary = $this->financialSummary->calculate(
                products: $products->all(),
                discountAmount: (float) $data->discount_amount,
                paidAmount: (float) $data->paid_amount,
                currencyCode: $data->currency_code,
            );

            $amounts = $summary->get('amounts');
            $baseAmounts = $summary->get('baseAmounts');

            $exchangeRate = $summary->get('rate');

            /*
             * ---------------------------------------------------------
             * 3. Update Purchase Invoice
             * ---------------------------------------------------------
             *
             * Do not regenerate the system invoice number or sequence.
             * Those identify the existing invoice.
             */

            $invoice->update([
                'party_id' => $data->party_id,
                'currency_id' => $data->currency_id,
                'hawala_office_id' => $data->hawala_office_id ?? null,
                'original_invoice_number' => $data->original_invoice_number,
                'date' => $data->date,
                'exchange_rate' => $exchangeRate,

                'subtotal' => $amounts->get('subtotal'),
                'discount_amount' => $amounts->get('discount_amount'),
                'total_amount' => $amounts->get('total_amount'),
                'paid_amount' => $amounts->get('paid_amount'),
                'base_paid_amount' => $baseAmounts->get('paid_amount'),
                'base_subtotal' => $baseAmounts->get('subtotal'),
                'base_discount_amount' => $baseAmounts->get('discount_amount'),
                'base_total_amount' => $baseAmounts->get('total_amount'),

                'payment_method' => $data->payment_method,
                'payment_status' => $summary->get('paymentState'),
                'status' => $invoice->status,
            ]);

            /*
             * ---------------------------------------------------------
             * 4. Replace Purchase Invoice Lines
             * ---------------------------------------------------------
             *
             * Since the financial summary is recalculated from the
             * submitted products, remove the old lines and recreate them.
             */

            $invoice->lines()->delete();

            $linesData = $summary->get('lineAmounts');

            $invoice->lines()->createMany(
                $products->map(function (array $product) use (
                    $data,
                    $exchangeRate,
                    $linesData
                ) {
                    $line = $linesData->get($product['product_variant_id']);

                    return [
                        'product_variant_id' => $product['product_variant_id'],
                        'created_by' => Auth::id(),
                        'currency_id' => $data->currency_id,
                        'exchange_rate' => $exchangeRate,
                        'quantity' => $line['quantity'],
                        'received_quantity' => 0,
                        'unit_cost' => $line['unit_cost'],
                        'total_cost' => $line['total_cost'],
                        'base_unit_cost' => $line['base_unit_cost'],
                        'base_total_cost' => $line['base_total_cost'],
                    ];
                })->all()
            );

            /*
             * ---------------------------------------------------------
             * 5. Return updated invoice
             * ---------------------------------------------------------
             */

            return $invoice->fresh('lines');
        });
    }
}
