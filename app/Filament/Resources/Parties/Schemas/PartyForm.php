<?php

namespace App\Filament\Resources\Parties\Schemas;

use App\Enums\PartyType;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\JsContent;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Text;

class PartyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('General')
                    ->columns(4)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->autocapitalize('words')
                            ->afterStateUpdated(function ($livewire, $component) {
                                $livewire->validateOnly($component->getStatePath());
                            }),
                        Select::make('type')
                            ->options(PartyType::class)
                            ->native(false)
                            ->live()
                            ->required(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email(),
                        Checkbox::make('is_active')
                            ->inline(false)
                            ->default(true)
                            ->required(),
                        Repeater::make('phone')
                            ->simple(
                                TextInput::make('phone')->tel(),
                            )->columnSpanFull(),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
                Fieldset::make('Accounting')
                    ->columns(4)
                    ->hiddenOn(['edit', 'view'])
                    ->schema([
                        Select::make('currency_id')
                            ->relationship(
                                name: 'currency',
                                titleAttribute: 'code',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true)
                            )
                            ->searchable(['name', 'code'])
                            ->preload()
                            ->native(false)
                            ->required(),
                        TextInput::make('opening_balance')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->visible(fn(Get $get): bool => $get('type') == PartyType::HawalaOffice)
                            ->columnSpan(2)
                            ->belowContent([
                                Icon::make(Heroicon::InformationCircle),
                                Text::make(JsContent::make(
                                    <<<'JS'
                                    $get('opening_balance') < 0 ? 'ما به صرافی بدهکار هستیم' : 'صرافی به ما بدهکار است.'
                                    JS
                                ))
                            ]),
                        TextInput::make('opening_payable')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->visible(fn(Get $get): bool => $get('type') == PartyType::Vendor || $get('type') == PartyType::VendorCustomer)
                            ->belowContent([
                                Icon::make(Heroicon::InformationCircle),
                                Text::make(JsContent::make(
                                    <<<'JS'
                                    $get('opening_payable') < 0 ? 'فروشنده به ما بدهکار است.' : 'ما به فروشنده بدهکار هستیم.'
                                    JS
                                ))
                            ]),
                        TextInput::make('opening_receivable')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->visible(fn(Get $get): bool => $get('type') == PartyType::Customer || $get('type') == PartyType::VendorCustomer)
                            ->belowContent([
                                Icon::make(Heroicon::InformationCircle),
                                Text::make(JsContent::make(
                                    <<<'JS'
                                    $get('opening_receivable') < 0 ? 'ما به مشتری بدهکار هستیم.' : 'مشتری به ما بدهکار است.'
                                    JS
                                ))
                            ]),
                        TextInput::make('limit')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
            ])->columns(1);
    }
}
