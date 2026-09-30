<?php

namespace App\Services\Purchases;

use App\Enums\PaymentStatus;
use App\Models\Currency;
use App\Services\Accounting\ExchangeRateService;
use Illuminate\Support\Collection;

final class PurchaseInvoiceFinancialSummary
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    public function calculate(
        array $products,
        float $discountAmount,
        float $paidAmount,
        string $currencyCode,
    ): Collection {
        /*
         * ---------------------------------------------------------
         * 1. Calculate subtotal
         * ---------------------------------------------------------
         */

        $subtotal = collect($products)->sum('total_cost');

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

        $lineAmounts = collect($products)
            ->map(function (array $product) use (
                $currencyCode,
                $baseCurrencyCode,
            ) {
                $unitCost = (float) $product['unit_cost'];
                $totalCost = (float) $product['total_cost'];

                $converted = $this->exchangeRateService->convertMany(
                    amounts: collect([
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                    ]),
                    fromCurrency: $currencyCode,
                    toCurrency: $baseCurrencyCode,
                );

                return [
                    'product_variant_id' => $product['product_variant_id'],
                    'quantity' => (float) $product['quantity'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'base_unit_cost' => $converted->get('unit_cost'),
                    'base_total_cost' => $converted->get('total_cost'),
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
