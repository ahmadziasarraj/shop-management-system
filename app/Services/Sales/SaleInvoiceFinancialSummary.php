<?php

namespace App\Services\Sales;

use App\Enums\PaymentStatus;
use App\Models\Currency;
use App\Services\Accounting\ExchangeRateService;
use Illuminate\Support\Collection;

final class SaleInvoiceFinancialSummary
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
    }

    public function calculate(
        Collection $products,
        float $discountAmount,
        float $paidAmount,
        string $currencyCode,
    ): Collection {
        /*
         * ---------------------------------------------------------
         * 1. Calculate subtotal
         * ---------------------------------------------------------
         */

        $subtotal = $products->sum('line_total');

        $totalAmount = $subtotal - $discountAmount;

        $balance = $totalAmount - $paidAmount;

        /*
         * ---------------------------------------------------------
         * 2. Payment status
         * ---------------------------------------------------------
         */

        $paymentStatus = match (true) {
            $balance > 0 => 'Due',
            $balance < 0 => 'Overpaid',
            default => 'Complete',
        };
        $paymentState = match (true) {
            ($balance > 0 && $paidAmount > 0) => PaymentStatus::PartiallyPaid,
            $balance > 0 => PaymentStatus::Unpaid,
            $balance < 0 && $paidAmount > 0 => PaymentStatus::Paid,
            default => PaymentStatus::NotApplicable,
        };

        /*
         * ---------------------------------------------------------
         * 3. Base currency
         * ---------------------------------------------------------
         */

        $baseCurrency = Currency::query()
            ->where('is_base', true)
            ->first();

        $baseCurrencyCode = $baseCurrency?->code ?? 'USD';

        /*
         * ---------------------------------------------------------
         * 4. Exchange rate
         * ---------------------------------------------------------
         *
         * Example:
         *
         * Base: USD
         * Invoice: AFN
         * Rate: 70
         *
         * 700 AFN = 10 USD
         */

        $rate = $this->exchangeRateService->getRate(
            fromCurrencyCode: $baseCurrencyCode,
            toCurrencyCode: $currencyCode,
        );

        /*
         * ---------------------------------------------------------
         * 5. Header amounts
         * ---------------------------------------------------------
         */

        $amounts = collect([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
        ]);

        $baseAmounts = $this->exchangeRateService->convertMany(
            amounts: $amounts,
            fromCurrency: $currencyCode,
            toCurrency: $baseCurrencyCode,
        );

        /*
         * ---------------------------------------------------------
         * 6. Line amounts
         * ---------------------------------------------------------
         */

        $lineAmounts = $products->map(function (array $product) use ($currencyCode, $baseCurrencyCode) {
            $unitPrice = (float) $product['unit_price'];
            $lineTotal = (float) $product['line_total'];

            $converted = $this->exchangeRateService->convertMany(
                amounts: collect([
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]),
                fromCurrency: $currencyCode,
                toCurrency: $baseCurrencyCode,
            );
            dd($converted, $product);

            return [
                'product_variant_id' => $product['product_variant_id'],
                'quantity' => (float) $product['quantity'],
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'base_unit_price' => $converted->get('unit_price'),
                'base_line_total' => $converted->get('line_total'),
            ];
        })->keyBy('product_variant_id');

        return collect([
            'amounts' => $amounts,
            'baseAmounts' => $baseAmounts,
            'lineAmounts' => $lineAmounts,
            'rate' => $rate,
            'paymentStatus' => $paymentStatus,
            'paymentState' => $paymentState,
            'currencyCode' => $currencyCode,
            'baseCurrencyCode' => $baseCurrencyCode,
        ]);
    }
}
