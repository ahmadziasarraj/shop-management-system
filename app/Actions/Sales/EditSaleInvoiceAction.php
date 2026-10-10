<?php

namespace App\Actions\Sales;

use App\Data\Sales\SaleInvoiceData;
use App\Enums\SalesInvoiceStatus;
use App\Models\SaleInvoice;
use App\Models\StockItem;
use App\Services\Sales\CogsAllocationService;
use App\Services\Sales\SaleInvoiceFinancialSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EditSaleInvoiceAction
{
    public function __construct(
        private readonly SaleInvoiceFinancialSummary $financialSummary,
    ) {}

    public function execute(
        SaleInvoice $invoice,
        SaleInvoiceData $data
    ): SaleInvoice {
        return DB::transaction(function () use ($invoice, $data) {

            /*
             * ---------------------------------------------------------
             * 1. Calculate each purchase line
             * ---------------------------------------------------------
             */

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
             * 3. Update Sale Invoice
             * ---------------------------------------------------------
             */

            $invoice->update([
                'party_id' => $data->party_id,
                'currency_id' => $data->currency_id,
                'created_by' => Auth::id(),
                'hawala_office_id' => $data->hawala_office_id ?? null,
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
                'net_profit_total' => $summary->get('net_profit_total'),
                'payment_method' => $data->payment_method,
                'payment_status' => $summary->get('paymentState'),
                'status' => $invoice->status,
            ]);

            /*
             * ---------------------------------------------------------
             * 4. Create Sale Invoice Lines
             * ---------------------------------------------------------
             * Since the financial summary is recalculated from the
             * submitted products, remove the old lines and recreate them.
             */

            $invoice->lines()->delete();

            $linesData = $summary->get('lineAmounts');
            $invoice->lines()->createMany(
                $products->map(function (array $product) use ($data, $exchangeRate, $linesData) {
                    $line = $linesData->get($product['product_variant_id']);
                    return [
                        'product_variant_id' => $product['product_variant_id'],
                        'currency_id' => $data->currency_id,
                        'exchange_rate' => $exchangeRate,
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'discount_amount' => 0,
                        'line_total' => $line['line_total'],
                        'base_unit_price' => $line['base_unit_price'],
                        'base_discount_amount' => 0,
                        'base_line_total' => $line['base_line_total'],
                        'cogs_total' => $line['cogs_total'],
                        'line_net_profit_total' => $line['base_line_total'] - $line['cogs_total'],
                        'cogs_justification' => $product['cogs_justification'],
                    ];
                })->all()
            );

            return $invoice->load('lines');
        });
    }
}
