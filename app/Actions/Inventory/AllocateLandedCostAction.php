<?php

namespace App\Actions\Inventory;

use App\Models\LandedCost;
use App\Services\Inventory\LandedCostAllocationService;
use Illuminate\Support\Facades\DB;

final class AllocateLandedCostAction
{
    public function __construct(
        private readonly LandedCostAllocationService $allocator,
    ) {
    }

    public function execute(LandedCost $landedCost, array $costAllocations): LandedCost {

        return DB::transaction(
            function () use ($landedCost, $costAllocations) {

                $landedCost->load([
                    'purchaseInvoice.lines.productVariant',
                ]);

                /*
                 * Remove previous draft allocations.
                 *
                 * We can safely do this while
                 * the landed cost is still draft.
                 */
                $landedCost->allocations()->delete();

                $this->allocator->allocate(
                    $landedCost,
                    $costAllocations
                );

                return $landedCost->fresh([
                    'allocations',
                    'allocations.productVariant',
                ]);
            }
        );
    }
}
