<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('party_id')
                    ->relationship('party', 'name')
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                
                TextInput::make('payment_number')
                    ->required(),
                Select::make('payment_method')
                    ->options(PaymentMethod::class)
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DatePicker::make('payment_date')
                    ->required(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
