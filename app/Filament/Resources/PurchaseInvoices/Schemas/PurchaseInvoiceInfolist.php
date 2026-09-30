<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Services\Purchases\PurchaseInvoiceFinancialSummary;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class PurchaseInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('General Informations')
                    ->schema([
                        TextEntry::make('party.name')
                            ->label('Party')
                            ->placeholder('-'),
                        TextEntry::make('currency.code')
                            ->label('Currency'),
                        TextEntry::make('payment_method')
                            ->badge(),
                        TextEntry::make('hawalaOffice.name')
                            ->label('Hawala office')
                            ->placeholder('-'),
                        TextEntry::make('original_invoice_number'),
                        TextEntry::make('system_invoice_number'),
                        TextEntry::make('date')
                            ->label('Purchase Data')
                            ->date(),
                        TextEntry::make('payment_status')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        RepeatableEntry::make('lines')
                            ->label('Purchased Products')
                            ->table([
                                TableColumn::make('Product')->width(400),
                                TableColumn::make('Quantity')->width(200),
                                TableColumn::make('Unit Cost')->width(200),
                                TableColumn::make('Total Cost')->width(200),
                                TableColumn::make('Received')->width(200),
                            ])
                            ->schema([
                                TextEntry::make('productVariant.name'),
                                TextEntry::make('quantity')->numeric(decimalPlaces: 0)->suffix(fn(PurchaseInvoiceLine $record) => ' ' . $record->productVariant->unitMeasurement->symbol),
                                TextEntry::make('unit_cost')->money(fn(PurchaseInvoiceLine $record) => $record->currency->code),
                                TextEntry::make('total_cost')->money(fn(PurchaseInvoiceLine $record) => $record->currency->code),
                                TextEntry::make('received_quantity')->numeric(decimalPlaces: 0)->suffix(fn(PurchaseInvoiceLine $record) => ' ' . $record->productVariant->unitMeasurement->symbol),
                            ])
                            ->columnSpanFull(),
                    ])->columnSpanFull()->columns(4),
                TextEntry::make('summary')
                    ->columnSpanFull()
                    ->state(function (PurchaseInvoice $record): View {

                        $summary = app(PurchaseInvoiceFinancialSummary::class)->calculate(
                            products: $record->lines->toArray() ?? [],
                            discountAmount: (float) ($record->discount_amount ?? 0),
                            paidAmount: (float) ($record->paid_amount ?? 0),
                            currencyCode: $record->currency->code,
                        );
                        return view(
                            'livewire.purchase-invoice-financial-summary',
                            [
                                'amounts' => $summary->get('amounts'),
                                'baseAmounts' => $summary->get('baseAmounts'),
                                'currencyCode' => $summary->get('currencyCode'),
                                'baseCurrencyCode' => $summary->get('baseCurrencyCode'),
                                'rate' => number_format($summary->get('rate'), 2),
                                'paymentStatus' => $summary->get('paymentStatus'),
                            ],
                        );
                    })
                    ->html()->hiddenLabel()

            ]);
    }
}
