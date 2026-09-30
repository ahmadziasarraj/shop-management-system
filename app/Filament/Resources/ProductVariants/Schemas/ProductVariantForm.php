<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\UnitMeasurement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name', 'sku'])
                    ->preload()
                    ->native(false)
                    ->required()
                    ->columnSpanFull(),
                ...static::variantFormFields(),
            ])->columns(4);
    }

    public static function variantFormFields(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($livewire, $component) {
                    $livewire->validateOnly($component->getStatePath());
                })
                ->columnSpan(2),
            Select::make('unit_measurement_id')
                ->relationship(
                    name: 'unitMeasurement',
                    titleAttribute: 'name',
                )
                ->searchable(['name', 'symbol'])
                ->columnSpan(2)
                ->preload()
                ->native(false)
                ->required()
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->dehydrateStateUsing(fn(string $state): string => ucwords($state))
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($livewire, $component) {
                            $livewire->validateOnly($component->getStatePath());
                        })
                        ->maxLength(255),
                    TextInput::make('symbol')
                        ->required()
                        ->dehydrateStateUsing(fn(string $state): string => strtolower($state))
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($livewire, $component) {
                            $livewire->validateOnly($component->getStatePath());
                        })
                        ->maxLength(255),
                ])->columns(2)
                ->createOptionUsing(function (array $data): int {
                    $data['symbol'] = strtolower($data['symbol']);
                    return UnitMeasurement::create($data)->getKey();
                }),
            TextInput::make('model'),
            TextInput::make('company'),
            TextInput::make('cost_price')
                ->numeric()
                ->prefix('$'),
            TextInput::make('selling_price')
                ->numeric()
                ->prefix('$'),
            DatePicker::make('expiry_date')
                ->native(false)
                ->firstDayOfWeek(7),
            Toggle::make('is_active')
                ->required()
                ->default(true)
                ->inline(false),
            Textarea::make('notes')
                ->columnSpanFull(),
        ];
    }
}
