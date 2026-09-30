<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryCostLayer;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InventoryTransactionService
{
    public function __construct(
        private readonly WeightedAverageCostService $costing,
    ) {}

    public function receive(
        string $stockId,
        ProductVariant $product,
        float $quantity,
        float $unitCost,
        InventoryTransactionType $type = InventoryTransactionType::PurchaseReceipt,
        ?object $reference = null,
    ): array {

        if ($quantity <= 0) {
            throw new RuntimeException(
                'Received quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $stockId,
            $product,
            $quantity,
            $unitCost,
            $type,
            $reference,
        ) {

            $balance = $this->costing->receive(
                stockId: $stockId,
                product: $product,
                quantity: $quantity,
                unitCost: $unitCost,
            );

            $layer = InventoryCostLayer::create([
                'stock_id' => $stockId,
                'product_variant_id' => $product->id,
                'source_type' => $reference ? $reference::class : null,
                'source_id' => $reference?->id,
                'quantity' => $quantity,
                'quantity_consumed' => 0,
                'purchase_unit_cost' => $unitCost,
                'landed_cost_per_unit' => 0,
                'total_unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost
            ]);

            $movement = StockMovement::create([
                'stock_id' => $stockId,
                'product_variant_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->id,
                'created_by' => Auth::user()->id,
            ]);

            return [
                'balance' => $balance,
                'layer' => $layer,
                'movement' => $movement,
            ];
        });
    }

    public function issue(
        string $stockId,
        ProductVariant $product,
        float $quantity,
        ?object $reference = null,
    ): array {

        if ($quantity <= 0) {
            throw new RuntimeException(
                'Issued quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $stockId,
            $product,
            $quantity,
            $reference,
        ) {

            $result = $this->costing->issue(
                stockId: $stockId,
                product: $product,
                quantity: $quantity,
            );

            $this->consumeCostLayers(
                 stockId: $stockId,
                product: $product,
                quantity: $quantity,
            );

            $movement = StockMovement::create([
                'stock_id' => $stockId,
                'product_variant_id' => $product->id,
                'type' => InventoryTransactionType::SaleIssue,
                'quantity' => -$quantity,
                'unit_cost' => $result['unit_cost'],
                'total_cost' => $result['cogs'],
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->id,
                'created_by' => Auth::user()->id,
            ]);

            return [
                'balance' => $result['balance'],
                'movement' => $movement,
                'unit_cost' => $result['unit_cost'],
                'cogs' => $result['cogs'],
            ];
        });
    }

    private function consumeCostLayers(
        string $stockId,
        ProductVariant $product,
        float $quantity,
    ): void {

        $remaining = $quantity;

        $layers = InventoryCostLayer::query()
            ->where('stock_id', $stockId)
            ->where('product_variant_id', $product->id)
            ->whereColumn(
                'quantity_consumed',
                '<',
                'quantity'
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($layers as $layer) {

            if ($remaining <= 0) {
                break;
            }

            $available = (float) $layer->quantity - (float) $layer->quantity_consumed;

            $consume = min($available, $remaining);

            $layer->increment( 'quantity_consumed', $consume);

            $remaining -= $consume;
        }

        if ($remaining > 0) {
            throw new RuntimeException(
                'Unable to consume enough inventory cost layers.'
            );
        }
    }


    // These Are Pending for Implementation
    // $inventory->adjustIncrease(
    // ...
    // );

    // $inventory->adjustDecrease(
    //     ...
    // );

    // $inventory->customerReturn(
    //     ...
    // );

    // $inventory->supplierReturn(
    //     ...
    // );
}
