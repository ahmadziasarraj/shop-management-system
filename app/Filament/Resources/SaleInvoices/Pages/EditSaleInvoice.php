<?php

namespace App\Filament\Resources\SaleInvoices\Pages;

use App\Actions\Sales\EditSaleInvoiceAction;
use App\Data\Sales\SaleInvoiceData;
use App\Filament\Resources\SaleInvoices\SaleInvoiceResource;
use App\Models\SaleInvoice;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSaleInvoice extends EditRecord
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            // DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $record = SaleInvoice::findOrFail($data['id']);
        $data['currency_code'] = $record->currency->code;
        $data['sold_products'] = $record->lines
            ->loadMissing('productVariant.unitMeasurement')
            ->map(function ($line) {
                return collect([
                    'product_variant_id' => $line['product_variant_id'],
                    'unit_measurement' => $line->productVariant->unitMeasurement?->symbol,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => round((float) $line['line_total'], 2),
                    'cogs_justification' => $line['cogs_justification'],
                ]);
            })
            ->values()
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $invoiceData = SaleInvoiceData::from($data);
        return app(EditSaleInvoiceAction::class)->execute($record, $invoiceData);
    }
}
