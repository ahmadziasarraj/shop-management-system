<?php

namespace App\Actions\Sales;

use App\Services\Inventory\WeightedAverageCostService;
use Illuminate\Support\Facades\DB;

final class PostSaleAction
{
    public function __construct(
        private readonly WeightedAverageCostService $costService,
    ) {
    }

    public function execute($sale): void
    {
        DB::transaction(function () use ($sale) {

            $totalCogs = 0;

            foreach ($sale->lines as $line) {

                if (! $line->productVariant->track_inventory) {
                    continue;
                }

                $result = $this->costService->issue(
                    stockId: $sale->stock_id,
                    product: $line->productVariant,
                    quantity: $line->quantity,
                );

                $line->update([
                    'unit_cost' => $result['unit_cost'],
                    'total_cost' => $result['cogs'],
                ]);

                $totalCogs += $result['cogs'];
            }

            /*
             * Accounting:
             *
             * Dr COGS
             *     Cr Inventory
             *
             * $totalCogs
             */

            // Create accounting journal here.
        });
    }
}
