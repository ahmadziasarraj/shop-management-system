<?php

namespace App\Filament\Resources\SaleInvoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SaleInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('party.name')
                    ->label('Party')
                    ->placeholder('-'),
                TextEntry::make('currency.name')
                    ->label('Currency'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('hawalaOffice.name')
                    ->label('Hawala office')
                    ->placeholder('-'),
                TextEntry::make('system_invoice_number'),
                TextEntry::make('invoice_sequence')
                    ->numeric(),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('exchange_rate')
                    ->numeric(),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('discount_amount')
                    ->numeric(),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('paid_amount')
                    ->numeric(),
                TextEntry::make('base_paid_amount')
                    ->numeric(),
                TextEntry::make('base_subtotal')
                    ->numeric(),
                TextEntry::make('base_discount_amount')
                    ->numeric(),
                TextEntry::make('base_total_amount')
                    ->numeric(),
                TextEntry::make('payment_method')
                    ->badge(),
                TextEntry::make('payment_status')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
