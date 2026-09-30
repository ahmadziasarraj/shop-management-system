<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductVariantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('product.name'),
                TextEntry::make('unitMeasurement.symbol'),
                TextEntry::make('sku')
                    ->label('SKU'),
                TextEntry::make('model')
                    ->placeholder('-'),
                TextEntry::make('company')
                    ->placeholder('-'),
                TextEntry::make('cost_price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('selling_price')
                    ->money()
                    ->placeholder('-'),
                IconEntry::make('expiry_date')
                    ->boolean()
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
