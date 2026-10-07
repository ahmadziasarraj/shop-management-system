<?php

namespace App\Filament\Resources\SaleInvoices\Pages;

use App\Actions\Sales\CreateSaleInvoiceAction;
use App\Data\Sales\SaleInvoiceData;
use App\Filament\Resources\SaleInvoices\SaleInvoiceResource;
use App\Models\SaleInvoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSaleInvoice extends CreateRecord
{
    protected static string $resource = SaleInvoiceResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        $invoiceData = SaleInvoiceData::from($data);
        return app(CreateSaleInvoiceAction::class)->execute($invoiceData);
    }
}
