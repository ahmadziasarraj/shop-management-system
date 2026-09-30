<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Actions\Purchases\CreatePurchaseInvoiceAction;
use App\Data\Purchases\PurchaseInvoiceData;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseInvoice extends CreateRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $invoiceData = PurchaseInvoiceData::from($data);
        return app(CreatePurchaseInvoiceAction::class)->execute($invoiceData);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->record,
            'open_approve' => 1
        ]);
    }

    // ->successRedirectUrl(fn (Model $record): string => route('posts.edit', [
    //     'post' => $record,
    // ]))
}
