<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\ProductVariants\Schemas\ProductVariantInfolist;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('sku')
                    ->label('SKU'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                RepeatableEntry::make('variants') // Should list the available variants first and number of abailable of them.
                    ->table([
                        TableColumn::make('name'),
                        TableColumn::make('Unit Measurement'),
                        TableColumn::make('SKU'),
                        TableColumn::make('is_active'),
                    ])
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('unitMeasurement.symbol'),
                        TextEntry::make('sku'),
                        IconEntry::make('is_active')
                            ->boolean(),
                    ])
                    ->columnSpanFull()
            ])->columns(3);
    }
}
