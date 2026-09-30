<?php

namespace App\Actions\Inventory;

use App\Enums\LandedCostStatus;
use App\Enums\PaymentStatus;
use App\Models\LandedCost;
use App\Models\PurchaseInvoice;
use App\Services\Accounting\ExchangeRateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreateLandedCostAction
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRates,
        private readonly AllocateLandedCostAction $landedCostAllocation,
    ) {}

    public function execute(array $landedCosts, PurchaseInvoice $invoice) : Collection
    {
        return DB::transaction(function () use ($landedCosts, $invoice) {
            $createdLandedCosts = new Collection();
            foreach ($landedCosts as $landedCost) {

                if ($landedCost['equal_allocation']) { // equal landed cost
                    $totalAmount = $landedCost['total_amount'];
                } else { // anequal landed cost
                    $totalAmount = collect($landedCost['landed_cost_allocations'])->sum(fn($allocation) => (float) $allocation['allocated_amount']);
                }
                $baseCurrency = $this->getBaseCurrency();
                $currencyCode = $this->getCurrencyCode($landedCost['currency_id']);
                $baseAmount = $this->exchangeRates->convert(
                    amount: $totalAmount,
                    fromCurrencyCode: $currencyCode->code,
                    toCurrencyCode: $baseCurrency->code,
                    date: now(),
                );
                $rate = $this->exchangeRates->getRate(
                    fromCurrencyCode: $baseCurrency->code,
                    toCurrencyCode: $currencyCode->code,
                    date: now(),
                );

                // dd($rate, 'landed costs--->', $landedCosts, 'item-->', $landedCost, 'base Amount', $baseAmount, 'total amount', $totalAmount);


                $record = LandedCost::create([
                    'purchase_invoice_id' => $invoice->id,
                    'party_id' => $landedCost['party_id'],
                    'currency_id' => $landedCost['currency_id'],
                    'hawala_office_id' => $landedCost['hawala_office_id'] ?? null,

                    'exchange_rate' => $rate,
                    'paid_amount' => $totalAmount,
                    'base_paid_amount' => $baseAmount,
                    'amount' => $totalAmount,
                    'base_amount' => $baseAmount,

                    'description' => $landedCost['description'] ?? null,
                    'payment_method' => $landedCost['payment_method'],
                    'payment_status' => PaymentStatus::Paid,
                    'status' => LandedCostStatus::Draft,
                    'created_by' => Auth::user()->id,
                ]);
                $costAllocations = [
                    'totalAmount' => $totalAmount,
                    'totalBaseAmount' => $baseAmount,
                    'isEqualAllocated' => $landedCost['equal_allocation'],
                    'rate' => $rate,
                    'totalQuantity' => $invoice->lines()->sum('quantity'),
                    'totalQuantityReceived' => $invoice->lines()->sum('received_quantity'),

                    'allocations' => collect($landedCost['landed_cost_allocations'])->mapWithKeys(function (array $allocation) {
                        return [
                            $allocation['purchase_invoice_line_id'] => [
                                ...$allocation
                            ],
                        ];
                    }),
                ];
                // dd($costAllocations, $invoice->lines);
                $this->landedCostAllocation->execute($record, $costAllocations);

                $createdLandedCosts->push($record);
            }
            return $createdLandedCosts;
        });
    }

    private function getBaseCurrency()
    {
        return \App\Models\Currency::query()
            ->where('is_base', true)
            ->first();
    }
    private function getCurrencyCode(string $currencyId)
    {
        return \App\Models\Currency::query()
            ->where('id', $currencyId)
            ->first();
    }
}
