<?php

namespace App\Actions\Purchases;

use App\Enums\PurchaseReceiptStatus;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseInvoice;
use App\Services\Inventory\InventoryReceiptService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReceivePurchaseAction
{
    public function __construct(
        private readonly InventoryReceiptService $inventory,
    ) {}

    public function execute(
        PurchaseInvoice $invoice,
        array $lines,
        string $stockId,
    ): PurchaseReceipt {

        return DB::transaction(
            function () use (
                $invoice,
                $lines,
                $stockId
            ) {

                $invoice->load(['lines.productVariant']);

                if ($invoice->status === 'cancelled') {
                    throw new RuntimeException(
                        'Cancelled purchase invoices cannot be received.'
                    );
                }

                $receipt = PurchaseReceipt::create([
                    'stock_id' => $stockId,
                    'purchase_invoice_id' => $invoice->id,
                    'receipt_number' => $this->generateReceiptNumber(),
                    'receipt_date' => now(),
                    'status' => PurchaseReceiptStatus::Draft,
                    'created_by' => Auth::id(),
                ]);

                foreach ($lines as $lineData) {

                    $invoiceLine = $invoice->lines->firstWhere('id', $lineData['purchase_invoice_line_id']);

                    if (! $invoiceLine) {
                        throw new RuntimeException(
                            'Purchase invoice line not found.'
                        );
                    }

                    $receivingQuantity = (float) $lineData['receiving_quantity'];
                    $alreadyReceived =(float) $invoiceLine->received_quantity;
                    $ordered =(float) $invoiceLine->quantity;
                    $remaining = $ordered - $alreadyReceived;

                    if ($receivingQuantity <= 0) {
                        // throw new RuntimeException(
                        //     'Receipt quantity must be greater than zero.'
                        // );
                        continue;
                    }

                    if ($receivingQuantity > $remaining) {
                        throw new RuntimeException(
                            'Cannot receive more than outstanding quantity.'
                        );
                    }

                    $receipt->lines()->create([
                        'stock_id' => $stockId,
                        'purchase_invoice_line_id' => $invoiceLine->id,
                        'product_variant_id' =>$invoiceLine->product_variant_id,
                        'quantity' => $receivingQuantity,
                        'unit_cost' => $invoiceLine->base_unit_cost,
                        'total_cost' => $receivingQuantity * $invoiceLine->base_unit_cost,
                    ]);
                }

                return $receipt->fresh([
                    'lines',
                ]);
            }
        );
    }

    private function generateReceiptNumber(): string
    {
        return 'GRN-' . now()->format('YmdHis');
    }
}
