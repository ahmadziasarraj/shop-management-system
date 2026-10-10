<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('party.name')
                    ->label('Party'),
                TextEntry::make('currency.name')
                    ->label('Currency'),
                TextEntry::make('reference_type'),
                TextEntry::make('reference_id'),
                TextEntry::make('payment_number'),
                TextEntry::make('payment_method')
                    ->badge(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('base_amount')
                    ->numeric(),
                TextEntry::make('exchange_rate')
                    ->numeric(),
                TextEntry::make('payment_date')
                    ->date(),
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
