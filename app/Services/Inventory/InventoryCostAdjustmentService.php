<?php

namespace App\Services\Inventory;

use App\Models\InventoryCostLayer;
use App\Models\LandedCost;
use App\Models\LandedCostAllocation;
use Illuminate\Support\Facades\DB;

final class InventoryCostAdjustmentService
{
    public function apply(
        LandedCost $landedCost,
        LandedCostAllocation $allocation,
        float $inventoryAmount,
        float $cogsAmount,
    ): void {

        DB::transaction(function () use (
            $landedCost,
            $allocation,
            $inventoryAmount,
            $cogsAmount
        ) {

            $line = $allocation->purchaseInvoiceLine;

            /*
             * Increase the cost of remaining inventory.
             */
            // if ($inventoryAmount > 0) {

            //     $remainingQuantity = (float) $allocation->quantity_remaining;

            //     if ($remainingQuantity > 0) {

            //         $perUnitAdjustment = $inventoryAmount / $remainingQuantity;

            //         $layers = $line->costLayers()
            //             ->whereColumn(
            //                 'quantity_consumed',
            //                 '<',
            //                 'quantity'
            //             )
            //             ->lockForUpdate()
            //             ->get();

            //         foreach ($layers as $layer) {
            //             $remainingLayerQuantity = (float) $layer->quantity - (float) $layer->quantity_consumed;
            //             $layerAdjustment = $remainingLayerQuantity * $perUnitAdjustment;
            //             $this->adjustLayer($layer, $layerAdjustment);
            //         }
            //     }
            // }
            if ($inventoryAmount > 0) {

                $remainingQuantity = (float) $allocation->quantity_remaining;

                if ($remainingQuantity > 0) {

                    $perUnitAdjustment = $inventoryAmount / $remainingQuantity;
                    // dd($landedCost->costLayers);

                    $layers = $line->costLayers()
                        ->where('landed_cost_id', $landedCost->id)
                        ->lockForUpdate()
                        ->get();

                    foreach ($layers as $layer) {
                        $remainingLayerQuantity = (float) $layer->quantity - (float) $layer->quantity_consumed;
                        $layerAdjustment = $remainingLayerQuantity * $perUnitAdjustment;
                        $this->adjustLayer($layer, $layerAdjustment);
                    }
                }
            }

            /*
             * COGS adjustment is handled by accounting.
             *
             * We DO NOT simply change historical sales.
             */
            if ($cogsAmount > 0) {

                /*
                 * Later:
                 *
                 * Dr COGS
                 *     Cr AP
                 *
                 * OR
                 *
                 * Dr COGS
                 *     Cr Accrued Freight
                 */
            }
        });
    }

    private function adjustLayer(
        InventoryCostLayer $layer,
        float $amount
    ): void {

        $remaining = (float) $layer->quantity - (float) $layer->quantity_consumed;

        if ($remaining <= 0) {
            return;
        }

        $currentTotal = (float) $layer->total_cost;
        $newTotal = $currentTotal + $amount;
        $newUnitCost = $newTotal / (float) $layer->quantity;
        $landedCostPerUnit = (float) $layer->landed_cost_per_unit + ($amount / (float) $layer->quantity);

        $layer->update([
            'landed_cost_per_unit' => $landedCostPerUnit,
            'total_unit_cost' => $newUnitCost,
            'total_cost' => $newTotal,
        ]);
    }
}
