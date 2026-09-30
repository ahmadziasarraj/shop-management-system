<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Actions\Purchases\EditPurchaseInvoiceAction;
use App\Data\Purchases\PurchaseInvoiceData;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Models\ProductVariant;
use App\Models\PurchaseInvoice;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseInvoice extends EditRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            // DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {

        $record = PurchaseInvoice::where('id', $data['id'])->first();
        $data['currency_code'] = $record->currency->code;
        $data['purchased_products'] = $record->lines
            ->loadMissing('productVariant.unitMeasurement')
            ->map(function ($line) {
                return collect([
                    'product_variant_id' => $line['product_variant_id'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'total_cost' => round((float) $line['total_cost'], 2),
                    'unit_measurement' => $line->productVariant->unitMeasurement?->symbol,
                ]);
            })
            ->values()
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $invoiceData = PurchaseInvoiceData::from($data);
        return app(EditPurchaseInvoiceAction::class)->execute($record, $invoiceData);
    }
}
