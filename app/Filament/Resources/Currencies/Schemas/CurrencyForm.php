<?php

namespace App\Filament\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('fields.currency_name'))
                    ->required()
                    ->maxLength(100)
                    ->unique(
                        table: 'currencies',
                        column: 'name',
                        ignoreRecord: true,
                    ),

                TextInput::make('code')
                    ->label(__('fields.currency_code'))
                    ->required()
                    ->maxLength(3)
                    ->unique(
                        table: 'currencies',
                        column: 'code',
                        ignoreRecord: true,
                    ),

                TextInput::make('decimal_places')
                    ->label(__('fields.decimal_places'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(6)
                    ->default(2)
                    ->required(),

                Toggle::make('is_base')
                    ->label(__('fields.is_base_currency'))
                    ->default(false),

                Toggle::make('is_active')
                    ->label(__('fields.is_active'))
                    ->default(true),
            ])->columns(3);
    }
}
