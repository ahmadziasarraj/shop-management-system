<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\ProductVariants\ProductVariantResource;
use App\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($livewire, $component) {
                        $livewire->validateOnly($component->getStatePath());
                    })
                    ->columnSpan(4),
                Toggle::make('is_active')
                    ->required()
                    ->inline(false)
                    ->default(true),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Repeater::make('variants')
                    ->relationship()
                    ->label('Product Variants')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        ...ProductVariantForm::variantFormFields()
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        $data['sku'] = 'VAR-' . now()->format('YmdHis'). '-' . strtoupper(Str::random(5));
                        return $data;
                    })
            ])->columns(5);
    }
}
