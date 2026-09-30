<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use App\Models\Currency;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FusedGroup::make([
                    TextInput::make('rate')
                        ->required()
                        ->prefix(function (): string {
                            $baseCurrency = Currency::all()->where('is_base', true)->first();
                            return $baseCurrency->code ?? '';
                        })
                        ->numeric()
                        ->columnSpan(2),
                    Select::make('to_currency_id')
                        ->relationship(
                            name: 'toCurrency',
                            titleAttribute: 'code',
                            modifyQueryUsing: fn(Builder $query) => $query->where('is_base', false)
                        )
                        ->searchable(['name', 'code'])
                        ->preload()
                        ->native(false)
                        ->required()
                        ->placeholder('Currency'),
                ])
                    ->label('From Currency | Rate | To Currency')
                    ->columns(3),
                DatePicker::make('effective_at')
                    ->required()
            ]);
    }
}
