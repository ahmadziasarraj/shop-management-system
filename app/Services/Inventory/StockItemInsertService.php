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