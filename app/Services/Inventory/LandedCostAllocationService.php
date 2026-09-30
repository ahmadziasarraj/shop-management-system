<?php

namespace App\Services\Inventory;

use App\Models\LandedCost;
use App\Models\PurchaseInvoiceLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LandedCostAllocationService
{
    public function allocate(LandedCost $landedCost, array $costAllocations): Collection
    {
        return DB::transaction(function () use ($landedCost, $costAllocations) {

            $lines = $landedCost
                ->purchaseInvoice
                ->lines()
                ->with('productVariant')
                ->get();

            if ($lines->isEmpty()) {
                throw new RuntimeException(
                    'Purchase invoice has no inventory lines.'
                );
            }

            return $this->allocateByQuantity(
                $landedCost,
                $lines,
                $costAllocations
            );

            // return match ($landedCost->allocation_method) {

            //     'quantity' => $this->allocateByQuantity(
            //         $landedCost,
            //         $lines
            //     ),

            //     'value' => $this->allocateByValue(
            //         $landedCost,
            //         $lines
            //     ),

            //     'weight' => $this->allocateByWeight(
            //         $landedCost,
            //         $lines
            //     ),

            //     'manual' => $this->allocateManually(
            //         $landedCost,
            //         $lines
            //     ),

            //     default => throw new RuntimeException(
            //         "Unsupported allocation method: "
            //         . $landedCost->allocation_method
            //     ),
            // };
        });
    }

    private function allocateByQuantity(
        LandedCost $landedCost,
        Collection $lines,
        array $costAllocations
    ): Collection {

        return $lines->map(
            function (PurchaseInvoiceLine $line) use (
                $landedCost,
                $costAllocations
            ) {
                $lineAlocation = $costAllocations['allocations']->get($line->id);
                $totalAffectedQuantity = $costAllocations['allocations']->sum(fn($allocation) => $allocation['quantity']);
                if ($costAllocations['isEqualAllocated']) {  // Equal cost allocation
                    $allocated = (float) $costAllocations['totalAmount'] /  (float) $totalAffectedQuantity * $lineAlocation['quantity'];
                    $baseAllocated = $allocated / $costAllocations['rate'];
                } else { // Manual cost allocation
                    $allocated = (float)$lineAlocation['allocated_amount'];
                    $baseAllocated = $allocated / $costAllocations['rate'];
                }

                return $this->createAllocation(
                    $landedCost,
                    $line,
                    'quantity',
                    $lineAlocation['quantity'],
                    $allocated,
                    $baseAllocated
                );
            }
        );
    }


    private function createAllocation(
        LandedCost $landedCost,
        PurchaseInvoiceLine $line,
        string $basis,
        float $basisValue,
        float $amount,
        float $baseAmount
    ) {

        return $landedCost->allocations()->create([
            'purchase_invoice_line_id' => $line->id,
            'product_variant_id' => $line->product_variant_id,
            'allocation_basis' => $basis,
            'basis_value' => $basisValue,
            'allocated_amount' => $amount,
            'base_allocated_amount' => $baseAmount,
        ]);
    }
}
