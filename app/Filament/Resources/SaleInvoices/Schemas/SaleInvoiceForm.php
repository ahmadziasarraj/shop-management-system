<?php

namespace App\Filament\Resources\SaleInvoices\Schemas;

use App\Enums\PartyType;
use App\Enums\PaymentMethod;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductVariant;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SaleInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('General Invoice Info')
                    ->schema([
                        Select::make('party_id')
                            ->relationship(
                                name: 'party',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->whereIn('type', [PartyType::Customer, PartyType::VendorCustomer])
                            )
                            ->searchable(['name'])
                            ->preload()
                            ->native(false)
                            ->required(),
                        Select::make('currency_id')
                            ->relationship(
                                name: 'currency',
                                titleAttribute: 'code',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true)
                            )
                            ->searchable(['name', 'code'])
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $currency = Currency::where('id', $state)->first();
                                $set('currency_code', $currency?->code ?? 'USD');
                            }),
                        Hidden::make('currency_code')->default('USD')->live(),
                        Select::make('payment_method')
                            ->options(PaymentMethod::class)
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('hawala_office_id')
                            ->relationship(
                                name: 'hawalaOffice',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('type', PartyType::HawalaOffice)
                            )
                            ->visible(fn(Get $get): bool => $get('payment_method') == PaymentMethod::HawalaOffice)
                            ->searchable(['name'])
                            ->preload()
                            ->native(false)
                            ->required(),
                        DatePicker::make('date')
                            ->required()
                            ->native(false)
                            ->firstDayOfWeek(7)
                            ->label('Sale Date'),
                        Hidden::make('discount_amount')
                            ->default(0.0)
                            ->dehydrated(false),
                        TextInput::make('paid_amount')
                            ->required()
                            ->numeric()
                            ->default(0.0)
                            ->minValue(0)
                            ->suffix(JsContent::make(
                                <<<'JS'
                                    $get('currency_code') ? $get('currency_code') : 'USD'
                                    JS
                            ))
                            ->live(onBlur: true),
                        // ->hidden(fn(Get $get): bool => $get('payment_method') == PaymentMethod::Credit),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columnSpanFull()->columns(4),
                Fieldset::make('Sold Products')
                    ->schema([
                        Repeater::make('sold_products')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product')->width(400),
                                TableColumn::make('Quantity')->width(200),
                                TableColumn::make('Unit Price')->width(200),
                                TableColumn::make('Total Price')->width(200),
                            ])
                            ->reorderable(false)
                            ->columnSpanFull()
                            // ->compact()
                            ->schema([
                                Select::make('product_variant_id')
                                    ->searchable()
                                    ->native(false)
                                    ->columnSpan(2)
                                    ->options(function (): array {
                                        $products = Product::with([
                                            'variants' => fn($query) => $query->inStock(),
                                        ])->get();

                                        return $products
                                            ->mapWithKeys(fn($product) => [
                                                $product->name => $product->variants
                                                    ->pluck('name', 'id')
                                                    ->toArray(),
                                            ])
                                            ->filter(fn($variants) => !empty($variants))
                                            ->toArray();
                                    })
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return ProductVariant::query()
                                            ->inStock()
                                            ->where(function ($query) use ($search) {
                                                $query->where('name', 'like', "%{$search}%")
                                                    ->orWhere('sku', 'like', "%{$search}%");
                                            })
                                            ->limit(15)
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            $product = ProductVariant::where('id', $state)->first();
                                            $set('unit_measurement', $product->unitMeasurement->symbol);
                                        }
                                        $set('quantity', 0);
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Hidden::make('unit_measurement')->live()->default(''),
                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state, $livewire, $component) {
                                        $total = $get('unit_price') * $state;
                                        $set('line_total', $total);
                                        $livewire->validateOnly($component->getStatePath());
                                    })
                                    ->suffix(JsContent::make(
                                        <<<'JS'
                                        $get('unit_measurement') ? $get('unit_measurement') : ''
                                        JS
                                    ))
                                    ->rules([
                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            $quantity = (int) ($value ?? 0);
                                            $available = 0;
                                            if ($get('product_variant_id')) {
                                                $productVariant = ProductVariant::query()->where('id', $get('product_variant_id'))->first();
                                                $available = (int) $productVariant->inStockItems()->sum('quantity_on_hand');
                                            }

                                            $remain = $available - $quantity;
                                            if ($remain < 0) {
                                                $fail("Available items: {$available}, but you entered: {$quantity}.");
                                            }
                                        },
                                    ])
                                    ->placeholder('quantity'),
                                TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->default(0.0)
                                    ->minValue(0.0)
                                    ->live(onBlur: true)
                                    ->suffix(JsContent::make(
                                        <<<'JS'
                                    $get('../../currency_code') ? $get('../../currency_code') : 'USD'
                                    JS
                                    ))
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $total = $get('quantity') * $state;
                                        $set('line_total', $total);
                                    })
                                    ->placeholder('unit cost'),
                                TextInput::make('line_total')
                                    ->disabled()
                                    ->default(0.0)
                                    ->dehydrated(false)
                                    ->suffix(JsContent::make(
                                        <<<'JS'
                                    $get('../../currency_code') ? $get('../../currency_code') : 'USD'
                                    JS
                                    )),
                            ])->columns(5)
                    ])->columnSpanFull(),
            ]);
    }
}
