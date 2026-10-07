<?php

namespace App\Actions\Sales;

use App\Data\Sales\SaleInvoiceData;
use App\Models\SaleInvoice;
use App\Services\Sales\SaleInvoiceFinancialSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateSaleInvoiceAction
{
    public function __construct(
        private readonly SaleInvoiceFinancialSummary $financialSummary,
    ) {
    }

    public function execute(
        SaleInvoiceData $data
    ): SaleInvoice {
        return DB::transaction(function () use ($data) {

            /*
             * ---------------------------------------------------------
             * 1. Calculate each purchase line
             * ---------------------------------------------------------
             */
            // dd($data->sold_products);

            $products = collect($data->sold_products)
                ->map(function (array $product) {

                    $quantity = (float) $product['quantity'];
                    $unitPrice = (float) $product['unit_price'];

                    return [
                        'product_variant_id' => $product['product_variant_id'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $quantity * $unitPrice,
                        'cogs_total' => $product['cogs_justification']['total_cogs'],
                        'cogs_justification' => $product['cogs_justification']
                    ];
                });

            /*
             * ---------------------------------------------------------
             * 2. Calculate invoice financial summary
             * ---------------------------------------------------------
             */

            $summary = $this->financialSummary->calculate(
                products: $products,
                discountAmount: (float) 0.0,
                paidAmount: (float) $data->paid_amount,
                currencyCode: $data->currency_code,
            );

            $amounts = $summary->get('amounts');
            $baseAmounts = $summary->get('baseAmounts');

            $exchangeRate = $summary->get('rate');

            /*
             * ---------------------------------------------------------
             * 3. Generate invoice number
             * ---------------------------------------------------------
             *
             * Replace this with your own InvoiceNumberService if
             * you already have one.
             */

            $invoiceSequence = $this->getNextInvoiceSequence();

            $systemInvoiceNumber = $this->generateSystemInvoiceNumber($invoiceSequence);

            /*
             * ---------------------------------------------------------
             * 4. Create Purchase Invoice
             * ---------------------------------------------------------
             */

            $invoice = PurchaseInvoice::query()->create([
                'party_id' => $data->party_id,
                'currency_id' => $data->currency_id,
                'created_by' => Auth::id(),
                'hawala_office_id' => $data->hawala_office_id ?? null,
                'original_invoice_number' => $data->original_invoice_number,
                'system_invoice_number' => $systemInvoiceNumber,
                'invoice_sequence' => $invoiceSequence,
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
                'status' => PurchaseInvoiceStatus::Draft,
            ]);

            /*
             * ---------------------------------------------------------
             * 5. Create Purchase Invoice Lines
             * ---------------------------------------------------------
             */
            $linesData = $summary->get('lineAmounts');
            $invoice->lines()->createMany(
                $products->map(function (array $product) use ($data, $exchangeRate, $linesData) {
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

            return $invoice->load('lines');
        });
    }

    /**
     * Get the next purchase invoice sequence.
     */
    private function getNextInvoiceSequence(): int
    {
        return ((int) PurchaseInvoice::query()->max('invoice_sequence')) + 1;
    }

    /**
     * Generate the system invoice number.
     *
     * Change the format to whatever numbering convention
     * you want to use in your system.
     */
    private function generateSystemInvoiceNumber(int $sequence): string
    {
        return 'PI-' . str_pad(
            (string) $sequence,
            6,
            '0',
            STR_PAD_LEFT
        ) . Str::random(3);
    }

}