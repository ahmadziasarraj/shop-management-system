<?php

namespace App\Filament\Resources\SaleInvoices\Pages;

use App\Actions\Sales\PostSaleInvoiceAction;
use App\Enums\SalesInvoiceStatus;
use App\Filament\Resources\SaleInvoices\SaleInvoiceResource;
use App\Models\SaleInvoice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewSaleInvoice extends ViewRecord
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn(Model $record): bool => $record->status == SalesInvoiceStatus::Draft),
            Action::make('approve')
                ->visible(fn(Model $record):bool => $record->status == SalesInvoiceStatus::Draft)
                ->action(function (SaleInvoice $invoice) {
                    app(PostSaleInvoiceAction::class)->execute(invoice: $invoice);
                })
        ];
    }
}
