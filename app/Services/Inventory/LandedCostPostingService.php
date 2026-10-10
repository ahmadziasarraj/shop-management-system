<?php

namespace App\Services\Inventory;

use App\Enums\CostLayerType;
use App\Enums\LandedCostStatus;
use App\Models\LandedCost;
use App\Models\LandedCostAllocation;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LandedCostPostingService
{
    public function __construct(
        private readonly InventoryCostAdjustmentService $costAdjustment,
        private readonly StockItemInsertService $stockItemUpsert,
    ) {}

    public function post(
        LandedCost $landedCost
    ): void {

        DB::transaction(function () use ($landedCost) {

            $landedCost->load([
                'allocations.purchaseInvoiceLine',
            ]);

            if ($landedCost->status !== LandedCostStatus::Draft) {
                throw new RuntimeException(
                    'Only draft landed costs can be posted.'
                );
            }

            if ($landedCost->allocations->isEmpty()) {
                throw new RuntimeException(
                    'Landed cost has no allocations.'
                );
            }

            foreach ($landedCost->allocations as $allocation) {
                // dd('allocations-->', $landedCost->allocations, 'alloca', $allocation);
                $this->processAllocation($allocation, $landedCost);
            }
        });
    }

    private function processAllocation(
        LandedCostAllocation $allocation,
        LandedCost $landedCost
    ): void {

        $line = $allocation->purchaseInvoiceLine;


        $purchased = (float) $line->quantity;
        // $consumed = (float) $line->stockItems->where('product_variant_id', $allocation->product_variant_id)->sum(fn($item) => $item->quantity_consumed);
        $received = (float) $allocation->basis_value;
        $allocated = (float) $allocation->base_allocated_amount;


        if ($purchased <= 0) {
            throw new RuntimeException(
                'Purchase quantity must be greater than zero.'
            );
        }
        // dd('received', $received, 'allocated', $allocated, 'cost per unite', $costPerUnit, 'cogs adjustment', $cogsAdjustment, 'inventory adjust', $inventoryAdjustment);


        $allocation->update([
            'quantity_purchased' => $purchased,
            'quantity_affected' => $received,
            'inventory_amount' => $allocated,
        ]);

        // $this->stockItemUpsert->upsertWithLandedCost($allocation);

        // $this->insertInventoryCostAdjustment($allocation, $landedCost);
        // $this->costAdjustment->apply(
        //     landedCost: $landedCost,
        //     allocation: $allocation,
        //     inventoryAmount: $inventoryAdjustment,
        //     cogsAmount: $cogsAdjustment,
        // );
    }
    private function insertInventoryCostAdjustment(
        LandedCostAllocation $allocation,
        LandedCost $landedCost
    ): void {
        $allocation->inventoryCostAdjustments()->create([
            'stock_id' => $allocation->purchaseInvoiceLine->purchaseReceipt->stock_id,
            'product_variant_id' => $allocation->product_variant_id,
            'landed_cost_id' => $allocation->landed_cost_id,
            'landed_cost_allocation_id' => $allocation->id,
            'quantity_affected' => $allocation->quantity_purchased,
            'unit_adjustment' => (float) $allocation->base_allocated_amount / $allocation->quantity_purchased,
            'amount' => (float) $allocation->base_allocated_amount,
            'type' => CostLayerType::LandedCost,
            'description' => $landedCost->description,
        ]);
    }
    private function insertStockItem(LandedCostAllocation $allocation): void
    {

        $stockItem = StockItem::query()->where('purchase_invoice_line_id', $allocation->purchaseInvoiceLine->id)->where('product_variant_id',  $allocation->product_variant_id)->first();

        $quantityPurchased = $allocation->quantity_purchased;
        $quantityConsumed = 0;
        $quantityRemaining = $quantityPurchased;

        $unitCost = $allocation->purchaseInvoiceLine->base_unit_cost;
        $landedCostPerUnit = $allocation->base_allocated_amount / $allocation->basis_value;
        $totalCogsAmount = $unitCost + $landedCostPerUnit;
        $inventoryAmount = ($unitCost + $landedCostPerUnit) * $quantityPurchased;
        // dd($inventoryAmount, $landedCostPerUnit * $quantityPurchased);

        if ($stockItem) { // update
            $newLandedCostPerUnit = $landedCostPerUnit + (float) $stockItem->landed_cost_amount;
            $totalCogsAmount =  (float) $stockItem->total_cogs_amount + $landedCostPerUnit;
            $inventoryAmount =  (float) $stockItem->inventory_amount + ($landedCostPerUnit * $quantityPurchased);

            $stockItem->update([
                'landed_cost_amount' => $newLandedCostPerUnit,
                'total_cogs_amount' => $totalCogsAmount,
                'inventory_amount' => $inventoryAmount,
                'has_remaining' => true,
            ]);
        } else { // create

            StockItem::create([
                'purchase_invoice_line_id' => $allocation->purchaseInvoiceLine->id,
                'product_variant_id' => $allocation->product_variant_id,

                'quantity_purchased' => $quantityPurchased,
                'quantity_consumed' => $quantityConsumed,
                'quantity_remaining' =>  $quantityRemaining,
                'purchased_amount' => $unitCost,
                'landed_cost_amount' => $landedCostPerUnit,
                'total_cogs_amount' => $totalCogsAmount,
                'inventory_amount' => $inventoryAmount,
                'has_remaining' => true,
            ]);
        }
    }
}
