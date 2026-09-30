<?php

namespace App\Actions\Purchases;

use App\Enums\PurchaseReceiptStatus;
use App\Models\PurchaseReceipt;
use App\Services\Inventory\InventoryReceiptService;
use App\Services\Purchases\PurchaseInvoiceStatusService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PostPurchaseReceiptAction
{
    public function __construct(
        private readonly InventoryReceiptService $inventory,
        private readonly PurchaseInvoiceStatusService $invoiceStatus,
    ) {
    }

    public function execute(PurchaseReceipt $receipt): PurchaseReceipt {

        return DB::transaction(
            function () use ($receipt) {

                $receipt->refresh();

                if ($receipt->status !== PurchaseReceiptStatus::Draft) {
                    throw new RuntimeException(
                        'Only draft receipts can be posted.'
                    );
                }

                if ($receipt->lines()->count() === 0) {
                    throw new RuntimeException(
                        'Receipt has no lines.'
                    );
                }

                $this->inventory->post($receipt);

                $receipt->update([
                    'status' => PurchaseReceiptStatus::Posted,
                ]);

                $this->invoiceStatus->update($receipt->purchaseInvoice);

                return $receipt->fresh();
            }
        );
    }
}
