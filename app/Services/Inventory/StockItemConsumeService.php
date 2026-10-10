<?php

namespace App\Services\Inventory;

use App\Models\StockItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockItemConsumeService
{
    public function __construct() {}

    public function consume(
        string $stockId,
        float $quantity
    ): void {
        DB::transaction(function () use ($stockId, $quantity) {
            $stockItem = StockItem::query()
                ->where('id', $stockId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stockItem->quantity_on_hand < $quantity) {
                throw new \Exception("Insufficient stock available for consumption.");
            }

            $stockItem->quantity_on_hand -= $quantity;
            $stockItem->quantity_consumed += $quantity;
            $stockItem->current_inventory -= $quantity * $stockItem->unit_total_cogs;
            $stockItem->save();
        });
    }
}
