<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class InventoryReceiptService
{
    public function __construct(
        private readonly WeightedAverageCostService $costService,
    ) {}

    public function post(PurchaseReceipt $receipt): void
    {

        DB::transaction(function () use ($receipt) {

            $receipt->load(['lines.productVariant']);

            foreach ($receipt->lines as $line) {
                $this->receiveLine(
                    $receipt,
                    $line
                );
            }
        });
    }

    private function receiveLine(PurchaseReceipt $receipt, PurchaseReceiptLine $line): void
    {

        $result = $this->costService->receive(
            stockId: $receipt->stock_id,
            product: $line->productVariant,
            quantity: (float) $line->quantity,
            unitCost: (float) $line->unit_cost,
        );

        /*
         * Create stock movement.
         */
        $receipt->stock->stockMovements()->create([
            'stock_id' => $receipt->stock_id,
            'product_variant_id' => $line->product_variant_id,
            'type' => InventoryTransactionType::PurchaseReceipt,
            'quantity' => $line->quantity,
            'unit_cost' => $result['total_cost'] / $line->quantity,
            'total_cost' => $result['total_cost'],
            'reference_type' => PurchaseReceipt::class,
            'reference_id' => $receipt->id,
            'created_by' => Auth::id()
        ]);



        /*
         * Update purchase invoice received quantity.
         */
        $line->purchaseInvoiceLine()->increment('received_quantity', $line->quantity);
    }
}
