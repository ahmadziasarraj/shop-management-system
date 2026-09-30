<?php

namespace App\Services\Inventory;

use App\Models\InventoryCostLayer;
use App\Models\LandedCost;
use Illuminate\Support\Facades\DB;

final class InventoryCostService
{
    public function applyLandedCost(LandedCost $landedCost): void {

        DB::transaction(function () use ($landedCost) {

            foreach ($landedCost->allocations as $allocation) {

                $line = $allocation->purchaseInvoiceLine;

                $quantity = $line->quantity;

                if ($quantity <= 0) {
                    continue;
                }

                $landedCostPerUnit = $allocation->base_allocated_amount / $quantity;

                $originalUnitCost = $line->base_unit_cost;

                $finalUnitCost = $originalUnitCost + $landedCostPerUnit;

                InventoryCostLayer::create([
                    'stock_id' => $line->purchaseReceipt->stock_id,
                    'product_variant_id' => $line->product_variant_id,
                    'purchase_invoice_line_id' => $line->id,
                    'landed_cost_id' => $landedCost->id,
                    'quantity' => $quantity,
                    'unit_cost' => $originalUnitCost,
                    'landed_cost_per_unit' => $landedCostPerUnit,
                    'total_unit_cost' => $finalUnitCost,
                    'total_cost' => $finalUnitCost * $quantity,
                    'quantity_consumed' => 0,
                ]);
            }
        });
    }
}
