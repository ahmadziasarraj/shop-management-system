<?php

namespace App\Services\Inventory;

use App\Models\PurchaseInvoice;
use App\Models\StockItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockItemReserveService
{
    public function __construct() {}

    public function reserve(
        string $stockId, float $quantity
    ): void {
        DB::transaction(function () use ($stockId, $quantity) {
            $stockItem = StockItem::query()
                ->where('stock_id', $stockId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stockItem->quantity_on_hand < $quantity) {
                throw new \Exception("Insufficient stock available for reservation.");
            }

            $stockItem->quantity_on_hand -= $quantity;
            $stockItem->quantity_reserved += $quantity;
            $stockItem->save();
        });
    }
}