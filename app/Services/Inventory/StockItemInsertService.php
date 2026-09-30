<?php

namespace App\Services\Inventory;

use App\Models\PurchaseInvoice;
use App\Models\StockItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockItemInsertService
{
    public function __construct() {}

    public function execute(
        PurchaseInvoice $invoice,
        array $receivedProducts,
        string $stockId
    ): void {
        DB::transaction(function () use ($invoice, $receivedProducts, $stockId) {
            $receivedProducts = collect($receivedProducts)->mapWithKeys(fn($product) => [$product['purchase_invoice_line_id'] => $product['receiving_quantity']]);

            foreach ($invoice->lines as $key => $line) {
                $quantity = (float) $receivedProducts->get($line->id);
                $amount = (float) $line->base_unit_cost;

                if ($quantity > 0) {
                    StockItem::create([
                        'stock_id' => $stockId,
                        'product_variant_id' => $line->product_variant_id,

                        'quantity' => $quantity,
                        'quantity_on_hand' => $quantity,

                        'purchased_amount' => $amount,
                        'unit_landed_cost' => 0,
                        'unit_total_cogs' => $amount,
                        'total_inventory' =>  $quantity * $amount,
                        'current_inventory' =>  $quantity * $amount,
                    ]);
                }
            }
        });
    }
    public function upsertWithLandedCosts(
        Collection $landedcosts,
        string $stockId
    ): void {
        DB::transaction(function () use ($landedcosts, $stockId) {

            $landedcosts
                ->flatMap->allocations
                ->groupBy('product_variant_id')
                ->each(function ($allocations, $productVariantId) use ($stockId) {
                    $first = $allocations->first();

                    $quantity = $first->quantity_affected;

                    if ($quantity > 0) {
                        $amount = $first->purchaseInvoiceLine->base_unit_cost;
                        $unitLandedCost = $allocations->sum('base_allocated_amount') / $quantity;
                        $unitTotalCogs = $amount + $unitLandedCost;
                        StockItem::create([
                            'stock_id' => $stockId,
                            'product_variant_id' => $productVariantId,

                            'quantity' => $quantity,
                            'quantity_on_hand' => $quantity,

                            'purchased_amount' => $amount,
                            'unit_landed_cost' => $unitLandedCost,
                            'unit_total_cogs' => $unitTotalCogs,
                            'total_inventory' =>  $unitTotalCogs * $quantity,
                            'current_inventory' =>  $unitTotalCogs * $quantity,
                        ]);
                    }
                });
        });
    }
}
    // public function upsertWithLandedCost(LandedCostAllocation $allocation): void
    // {
    //     DB::transaction(function () use ($allocation) {
    //         $line = $allocation->purchaseInvoiceLine;
    //         $quantityPurchased = (float) $line->quantity;
    //         $unitCost = (float) $line->base_unit_cost;
    //         $quantityReceived = $allocation->quantity_affected;
    //         $landedCostPerUnit = $quantityReceived > 0 ? $allocation->base_allocated_amount / $quantityReceived : 0;

    //         $totalCostPerUnit = $unitCost + $landedCostPerUnit;
    //         $inventoryAmount = $totalCostPerUnit * $quantityReceived;

    //         $stockItem = StockItem::query()
    //             ->where('purchase_invoice_line_id', $line->id)
    //             ->where('product_variant_id', $line->product_variant_id)
    //             ->lockForUpdate()
    //             ->first();

    //         if ($stockItem) {
    //             $this->updateStockItem(
    //                 $stockItem,
    //                 $line,
    //                 $line->received_quantity,
    //                 $quantityReceived,
    //                 $landedCostPerUnit,
    //                 true
    //             );
    //             return;
    //         }

    //         StockItem::create([
    //             'purchase_invoice_line_id' => $line->id,
    //             'product_variant_id' => $line->product_variant_id,

    //             'quantity_purchased' => $quantityPurchased,
    //             'quantity_received' => $line->received_quantity,
    //             'quantity_consumed' => 0,
    //             'quantity_remaining' => $line->quantity - $line->received_quantity,

    //             'purchased_amount' => $unitCost,
    //             'landed_cost_amount' => $landedCostPerUnit,
    //             'total_cogs_amount' => $totalCostPerUnit,
    //             'inventory_amount' => $inventoryAmount,

    //             'has_remaining' => $quantityPurchased > 0,
    //         ]);
    //     });
    // }

    // private function upsertStockItem(PurchaseInvoiceLine $line, float $receivedProducts): void
    // {

    //     $quantityPurchased = (float) $line->quantity;
    //     $unitCost = (float) $line->base_unit_cost;
    //     $landedCostPerUnit = 0.0;

    //     $totalCostPerUnit = $unitCost + $landedCostPerUnit;
    //     $quantityReceived = $receivedProducts;
    //     $inventoryAmount = $totalCostPerUnit * $quantityReceived;

    //     $stockItem = StockItem::query()
    //         ->where('purchase_invoice_line_id', $line->id)
    //         ->where('product_variant_id', $line->product_variant_id)
    //         ->lockForUpdate()
    //         ->first();

    //     if ($stockItem) {
    //         $this->updateStockItem(
    //             $stockItem,
    //             $line,
    //             $quantityReceived + (float) $line->received_quantity,
    //             $quantityReceived,
    //             $inventoryAmount,
    //             false
    //         );
    //         return;
    //     }

    //     StockItem::create([
    //         'stock_id' => $line->id,
    //         'product_variant_id' => $line->product_variant_id,

    //         'quantity' => $quantityPurchased,
    //         'quantity_consumed' => 0,
    //         'quantity_reserved' => 0,
    //         'quantity_on_hand' => $quantityPurchased - $quantityReceived,

    //         'purchased_amount' => $unitCost,
    //         'unit_landed_cost' => $landedCostPerUnit,
    //         'unit_total_cogs' => $totalCostPerUnit,
    //         'total_inventory' => $inventoryAmount,
    //     ]);
    // }

    // private function updateStockItem(
    //     StockItem $stockItem,
    //     PurchaseInvoiceLine $line,
    //     float $quantityReceived,
    //     float $quantityAffected,
    //     float $landedCostPerUnit,
    //     bool $hasLandedCost
    // ): void {


    //     if ($hasLandedCost) {
    //         $newReceivedQuantityAmount = ($landedCostPerUnit + $line->base_unit_cost) * $quantityAffected;
    //         $inventoryAmount = (float) $stockItem->inventory_amount + $newReceivedQuantityAmount;
    //     } else {
    //         $inventoryAmount = (float) $stockItem->inventory_amount + $landedCostPerUnit;
    //         $landedCostPerUnit = 0;
    //     }

    //     $stockItem->update([
    //         'quantity_received' => (float) $quantityReceived,
    //         'quantity_remaining' => (float) $line->quantity - $quantityReceived,

    //         'landed_cost_amount' => (float) $stockItem->landed_cost_amount + $landedCostPerUnit,
    //         'total_cogs_amount' => (float) $stockItem->total_cogs_amount + $landedCostPerUnit,
    //         'inventory_amount' => $inventoryAmount,

    //         'has_remaining' => true,
    //     ]);
    // }
