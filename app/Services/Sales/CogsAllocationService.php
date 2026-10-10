<?php

namespace App\Services\Sales;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\Currency;
use App\Models\PurchaseInvoice;
use App\Services\Accounting\ExchangeRateService;
use App\Models\StockItem;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class CogsAllocationService
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    public function emptyCogsSummary(
        string $variantId = '',
        float $quantityRequired = 0,
        string $method = 'system',
    ): array {
        return [
            'stock_items' => [],
            'stock_items_id' => [],
            'quantity_required' => $quantityRequired,
            'quantity_taken' => 0,
            'remaining' => $quantityRequired,
            'total_cogs' => 0,
            'variant_id' => $variantId,
            'total_profit' => 0,
            'method' => $method,
        ];
    }
    private function getFifoStocks(string $variantId)
    {
        return StockItem::query()
            ->with('productVariant.unitMeasurement')
            ->where('product_variant_id', $variantId)
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('created_at')
            ->get();
    }

    private function getManuallySelectedStocks(array $stockIds)
    {
        if (empty($stockIds)) {
            return collect();
        }

        $order = array_flip($stockIds);

        return StockItem::query()
            ->with('productVariant.unitMeasurement')
            ->whereIn('id', $stockIds)
            ->get()
            ->sortBy(fn(StockItem $stock) => $order[$stock->id]);
    }


    public function recalculateCogsJustification(
        Get $get,
        Set $set,
        ?string $method = null,
    ): void {
        $currencyCode = $get('currency_code') ?? 'USD';

        $newSoldProducts = collect($get('sold_products') ?? [])->map(function (array $soldProduct) use ($get, $method, $currencyCode) {
            $variantId = $soldProduct['product_variant_id'] ?? null;

            if (!$variantId) {
                $soldProduct['cogs_justification'] = $this->emptyCogsSummary();

                return $soldProduct;
            }

            $quantity = (float) ($soldProduct['quantity'] ?? 0);
            $unitPrice = (float) ($soldProduct['unit_price'] ?? 0);

            $cogsMethod = $method
                ?? $soldProduct['cogs_justification']['method']
                ?? 'system';

            $stockItemIds = $cogsMethod === 'manual'
                ? ($soldProduct['cogs_justification']['stock_items_id'] ?? [])
                : [];

            $soldProduct['cogs_justification'] = $this->calculate(
                selectedStockIds: $stockItemIds,
                variantId: $variantId,
                quantityRequired: $quantity,
                unitPrice: $unitPrice,
                currencyCode: $currencyCode,
                method: $cogsMethod,
            );

            return $soldProduct;
        });

        $set('sold_products', $newSoldProducts->toArray());
    }

    public function updateCogsAllocations(
        Set $set,
        Get $get,
        float $quantity,
        string $variantId,
        float $unitPrice,
        string $currencyCode,
    ): void {
        $method = $get('method') ?? 'system';

        if (blank($variantId) || $quantity <= 0) {
            $set('cogs_allocations', $this->emptyCogsSummary(
                variantId: $variantId,
                quantityRequired: $quantity,
                method: $method,
            ));

            return;
        }

        $set(
            'cogs_allocations',
            $this->calculate(
                selectedStockIds: $method === 'manual'
                    ? ($get('stock_items') ?? [])
                    : [],
                variantId: $variantId,
                quantityRequired: $quantity,
                unitPrice: $unitPrice,
                currencyCode: $currencyCode,
                method: $method,
            ),
        );
    }

    public function calculate(
        ?array $selectedStockIds,
        string $variantId,
        float $quantityRequired,
        float $unitPrice,
        string $currencyCode,
        string $method,
    ): array {
        $quantityRequired = max(0, $quantityRequired);

        if ($quantityRequired <= 0 || blank($variantId)) {
            return $this->emptyCogsSummary(
                variantId: $variantId,
                quantityRequired: $quantityRequired,
                method: $method,
            );
        }

        $stocks = $method === 'manual'
            ? $this->getManuallySelectedStocks($selectedStockIds ?? [])
            : $this->getFifoStocks($variantId);

        $baseCurrencyCode = Currency::query()
            ->where('is_base', true)
            ->value('code') ?? 'USD';

        $sellingPriceInBaseCurrency = $this->exchangeRateService->convert(
            amount: $unitPrice,
            fromCurrencyCode: $currencyCode,
            toCurrencyCode: $baseCurrencyCode,
        );

        $remaining = $quantityRequired;
        $totalCogs = 0;
        $totalProfit = 0;
        $items = [];
        $itemsId = [];

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $stock->quantity_on_hand;
            $unitCogs = (float) $stock->unit_total_cogs;
            $taken = min($available, $remaining);

            $layerCogs = $taken * $unitCogs;
            $layerProfit = ($sellingPriceInBaseCurrency - $unitCogs) * $taken;

            $items[] = [
                'stock_item_id' => $stock->id,
                'available' => $available,
                'unit_cogs' => $unitCogs,
                'taken' => $taken,
                'total_cogs' => $layerCogs,
                'profit' => $layerProfit,
                'measurement' => $stock->productVariant?->unitMeasurement?->name,
            ];

            $itemsId[] = $stock->id;

            $totalCogs += $layerCogs;
            $totalProfit += $layerProfit;
            $remaining -= $taken;
        }

        return [
            'stock_items' => $items,
            'stock_items_id' => $itemsId,
            'quantity_required' => $quantityRequired,
            'quantity_taken' => $quantityRequired - $remaining,
            'remaining' => max(0, $remaining),
            'total_cogs' => $totalCogs,
            'variant_id' => $variantId,
            'total_profit' => $totalProfit,
            'method' => $method,
        ];
    }
}
