<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExchangeRateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('fromCurrency.name')
                    ->label('From currency'),
                TextEntry::make('toCurrency.name')
                    ->label('To currency'),
                TextEntry::make('rate')
                    ->numeric(),
                TextEntry::make('effective_at')
                    ->dateTime(),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
