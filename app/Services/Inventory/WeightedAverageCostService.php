<?php

namespace App\Services\Inventory;

use App\Models\StockBalance;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WeightedAverageCostService
{
    public function receive(
        string $stockId,
        ProductVariant $product,
        float $quantity,
        float $unitCost,
    ): StockBalance {

        if ($quantity <= 0) {
            throw new RuntimeException(
                'Received quantity must be greater than zero.'
            );
        }

        if ($unitCost < 0) {
            throw new RuntimeException(
                'Unit cost cannot be negative.'
            );
        }

        return DB::transaction(function () use (
            $stockId,
            $product,
            $quantity,
            $unitCost,
        ) {

            $balance = StockBalance::query()
                ->where('stock_id', $stockId)
                ->where('product_variant_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = StockBalance::create([
                    'stock_id' => $stockId,
                    'product_variant_id' => $product->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'total_cost' => 0,
                    'average_cost' => 0,
                ]);
            }

            $oldQuantity = (float) $balance->quantity_on_hand;

            $oldValue = (float) $balance->total_cost;

            $receivedValue = $quantity * $unitCost;

            $newQuantity = $oldQuantity + $quantity;

            $newValue = $oldValue + $receivedValue;

            $newAverageCost = $newQuantity > 0 ? $newValue / $newQuantity : 0;

            $balance->update([
                'quantity_on_hand' => $newQuantity,
                'total_cost' => $newValue,
                'average_cost' => $newAverageCost,
            ]);

            return $balance->fresh();
        });
    }

    public function issue(
        string $stockId,
        ProductVariant $product,
        float $quantity,
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
        ) {

            $balance = StockBalance::query()
                ->where('stock_id', $stockId)
                ->where('product_variant_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw new RuntimeException(
                    'Inventory balance does not exist.'
                );
            }

            $available = (float) $balance->quantity_on_hand - (float) $balance->quantity_reserved;

            if ($quantity > $available) {
                throw new RuntimeException(
                    "Insufficient stock. " . "Available: {$available}"
                );
            }

            $averageCost = (float) $balance->average_cost;

            $cogs = $quantity * $averageCost;

            $newQuantity = (float) $balance->quantity_on_hand - $quantity;

            $newValue = (float) $balance->total_cost - $cogs;

            $newAverageCost = $newQuantity > 0 ? $newValue / $newQuantity : 0;

            $balance->update([
                'quantity_on_hand' => $newQuantity,
                'total_cost' => $newValue,
                'average_cost' => $newAverageCost,
            ]);

            return [
                'balance' => $balance->fresh(),
                'unit_cost' => $averageCost,
                'cogs' => $cogs,
                'quantity' => $quantity,
            ];
        });
    }
}
