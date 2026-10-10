<?php

namespace App\Filament\Resources\SaleInvoices\Schemas;

use App\Models\SaleInvoice;
use App\Models\SaleInvoiceLine;
use App\Services\Sales\SaleInvoiceFinancialSummary;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class SaleInvoiceInfolist
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
                        TextEntry::make('system_invoice_number'),
                        TextEntry::make('date')
                            ->label('Sale Data')
                            ->date(),
                        TextEntry::make('payment_status')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        RepeatableEntry::make('lines')
                            ->label('Sold Products')
                            ->table([
                                TableColumn::make('Product')->width(400),
                                TableColumn::make('Quantity')->width(200),
                                TableColumn::make('Unit Price')->width(200),
                                TableColumn::make('Total Price')->width(200),
                            ])
                            ->schema([
                                TextEntry::make('productVariant.name'),
                                TextEntry::make('quantity')->numeric(decimalPlaces: 0)->suffix(fn(SaleInvoiceLine $record) => ' ' . $record->productVariant->unitMeasurement->symbol),
                                TextEntry::make('unit_price')->money(fn(SaleInvoiceLine $record) => $record->currency->code),
                                TextEntry::make('line_total')->money(fn(SaleInvoiceLine $record) => $record->currency->code),
                            ])
                            ->columnSpanFull(),
                    ])->columnSpanFull()->columns(4),
                TextEntry::make('summary')
                    ->columnSpanFull()
                    ->state(function (SaleInvoice $record): View {
                        // dd($record->lines);

                        $summary = app(SaleInvoiceFinancialSummary::class)->calculate(
                            products: $record->lines ?? collect(),
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
