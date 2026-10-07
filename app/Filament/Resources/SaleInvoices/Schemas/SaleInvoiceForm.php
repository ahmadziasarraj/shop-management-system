<?php

namespace App\Filament\Resources\SaleInvoices\Schemas;

use App\Enums\PartyType;
use App\Enums\PaymentMethod;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Services\Accounting\ExchangeRateService;
use App\Services\Sales\CogsAllocationService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class SaleInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('General Invoice Info')
                    ->schema([
                        ...self::generalSaleFormFields()
                    ])->columnSpanFull()->columns(4),
                Fieldset::make('Sold Products')
                    ->schema([
                        ...self::soldProductsRepeaterFormField()
                    ])->columnSpanFull(),
            ]);
    }

    protected static function generalSaleFormFields(): array
    {
        return [
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
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    $currencyCode = Currency::find($state)?->code ?? 'USD';

                    $set('currency_code', $currencyCode);
                    app(CogsAllocationService::class)->recalculateCogsJustification(get: $get, set: $set);
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
                ->label('Sale Date')
                ->closeOnDateSelection()
                ->default(now()),
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
            Textarea::make('notes')
                ->columnSpanFull(),
        ];
    }

    protected static function soldProductsRepeaterFormField(): array
    {
        return [
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
                ->schema([
                    Select::make('product_variant_id')
                        ->label('Product')
                        ->placeholder('Search product or SKU...')
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->live()
                        ->getSearchResultsUsing(function (string $search): array {
                            return ProductVariant::query()
                                ->select([
                                    'id',
                                    'product_id',
                                    'name',
                                    'sku',
                                ])
                                ->with('product:id,name')
                                ->inStock()
                                ->where(function (Builder $query) use ($search) {
                                    $query
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('sku', 'like', "%{$search}%")
                                        ->orWhereHas(
                                            'product',
                                            fn(Builder $query) =>
                                                $query->where('name', 'like', "%{$search}%")
                                        );
                                })
                                ->orderBy('name')
                                ->limit(20)
                                ->pluck('name', 'id')
                                ->all();
                        })
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
                        // ->getOptionLabelUsing(
                        //     fn ($value): ?string =>
                        //         ProductVariant::query()
                        //             ->with('product:id,name')
                        //             ->find($value)?->select_label
                        // )

                        ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                            $set('quantity', 1);

                            if (blank($state)) {
                                $set('unit_measurement', null);
                                $set('cogs_justification', app(CogsAllocationService::class)->emptyCogsSummary());

                                return;
                            }

                            $variant = ProductVariant::query()
                                ->with('unitMeasurement')
                                ->find($state);

                            if (!$variant) {
                                $set('unit_measurement', null);
                                $set('cogs_justification', app(CogsAllocationService::class)->emptyCogsSummary());

                                return;
                            }

                            $set(
                                'unit_measurement',
                                $variant->unitMeasurement?->symbol
                            );

                            $set(
                                'cogs_justification',
                                app(CogsAllocationService::class)->calculate(
                                    selectedStockIds: [],
                                    variantId: $variant->id,
                                    quantityRequired: 1,
                                    unitPrice: (float) $get('unit_price'),
                                    currencyCode: $get('../../currency_code'),
                                    method: 'system',
                                )
                            );
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

                            if ($get('product_variant_id')) {
                                $set('cogs_justification', app(CogsAllocationService::class)->calculate(
                                    selectedStockIds: [],
                                    variantId: $get('product_variant_id'),
                                    quantityRequired: (float) $state,
                                    unitPrice: (float) ($get('unit_price') ?? 0),
                                    currencyCode: $get('../../currency_code'),
                                    method: 'system'
                                ));
                            }
                        })
                        ->suffix(JsContent::make(
                            <<<'JS'
                    $get('unit_measurement') ? $get('unit_measurement') : ''
                    JS
                        ))
                        ->rules([
                            fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                $variantId = $get('product_variant_id');

                                if (!$variantId) {
                                    return;
                                }

                                $quantity = (float) ($value ?? 0);

                                $available = (float) StockItem::query()
                                    ->where('product_variant_id', $variantId)
                                    ->where('quantity_on_hand', '>', 0)
                                    ->sum('quantity_on_hand');

                                if ($quantity > $available) {
                                    $fail(
                                        "Available items: {$available},but you entered: {$quantity}."
                                    );
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
                            if ($get('product_variant_id')) {
                                $set('cogs_justification', app(CogsAllocationService::class)->calculate(
                                    selectedStockIds: [],
                                    variantId: $get('product_variant_id'),
                                    quantityRequired: (float) ($get('quantity') ?? 0),
                                    unitPrice: (float) $state,
                                    currencyCode: $get('../../currency_code'),
                                    method: 'system'
                                ));
                            }
                        })
                        ->placeholder('unit cost'),
                    TextInput::make('line_total')
                        ->disabled()
                        ->default(0.0)
                        ->dehydrated()
                        ->suffix(JsContent::make(
                            <<<'JS'
                    $get('../../currency_code') ? $get('../../currency_code') : 'USD'
                    JS
                        )),
                    Hidden::make('cogs_justification')
                        ->live()
                        ->default(app(CogsAllocationService::class)->emptyCogsSummary()),
                ])->columns(5)
                ->extraItemActions([
                    Action::make('cogs')
                        ->disabled(function (Repeater $component, array $arguments): bool {
                            $itemData = $component->getRawItemState($arguments['item']);
                            if (!is_null($itemData['product_variant_id']) && $itemData['quantity'] > 0 && $itemData['unit_price'] > 0) {
                                return false;
                            }
                            return true;
                        })
                        ->label('COGS')
                        ->slideOver()
                        ->icon('heroicon-m-pencil-square')
                        ->modalHeading('COGS Allocation')
                        ->modalSubmitActionLabel('Apply')
                        ->modalSubmitAction(function (Action $parentAction) {
                            $data = $parentAction->getRawData();
                            if ($data['cogs_allocations']['remaining'] > 0) {
                                return false;
                            }
                        })
                        ->fillForm(function (Repeater $component, array $arguments): array {

                            $itemData = $component->getRawItemState($arguments['item']);
                            return [
                                'cogs_allocations' => $itemData['cogs_justification'],
                                'method' => array_key_exists('method', $itemData['cogs_justification']) ? $itemData['cogs_justification']['method'] : 'system',
                                'stock_items' => $itemData['cogs_justification']['stock_items_id']
                            ];
                        })
                        ->schema(function (Repeater $component, array $arguments, Get $get): array {
                            $itemData = $component->getRawItemState($arguments['item']);

                            $currencyCode = $get('currency_code');
                            $variantId = $itemData['product_variant_id'];
                            $quantity = (float) ($itemData['quantity'] ?? 0);
                            $unitPrice = (float) ($itemData['unit_price'] ?? 0);
                            if (!$variantId) {
                                return [];
                            }

                            $product = ProductVariant::query()->where('id', $variantId)->first();
                            $productInfo = "{$product->product->name} - {$product->name}";

                            return [
                                Callout::make('Insufficient Stock Available')
                                    ->description(function (Get $get): string {
                                        $remaining = (float) ($get('cogs_allocations.remaining') ?? 0);

                                        return "There is not enough stock to fulfill this quantity. "
                                            . "You still need {$remaining} additional units. "
                                            . "Please select more stock or reduce the required quantity.";
                                    })
                                    ->color('danger')
                                    ->icon(Heroicon::OutlinedExclamationTriangle)
                                    ->iconColor('danger')
                                    ->visible(fn(Get $get): bool => $get('cogs_allocations.remaining') > 0),
                                Section::make()
                                    ->heading("Cost of Goods Allocation for product: {$productInfo}")
                                    ->schema([
                                        Hidden::make('cogs_allocations')
                                            ->live()
                                            ->default(app(CogsAllocationService::class)->emptyCogsSummary()),
                                        Radio::make('method')
                                            ->label('COGS Method')
                                            ->options([
                                                'system' => 'System (FIFO)',
                                                'manual' => 'Manual',
                                            ])
                                            ->inline()
                                            ->default('system')
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(function (Set $set, Get $get) use ($quantity, $variantId, $unitPrice, $currencyCode) {
                                                $set('stock_items', null);
                                                app(CogsAllocationService::class)->updateCogsAllocations(
                                                    $set,
                                                    $get,
                                                    quantity: $quantity,
                                                    variantId: $variantId,
                                                    unitPrice: $unitPrice,
                                                    currencyCode: $currencyCode
                                                );
                                            }),
                                        Select::make('stock_items')
                                            ->label('Stock Item')
                                            ->options(function () use ($variantId) {
                                                if (!$variantId) {
                                                    return [];
                                                }

                                                return StockItem::query()
                                                    ->where('product_variant_id', $variantId)
                                                    ->where('quantity_on_hand', '>', 0)
                                                    ->get()
                                                    ->mapWithKeys(fn(StockItem $item) => [
                                                        $item->id => 'Available: ' . (float) $item->quantity_on_hand . ' — Cost: ' . Number::currency($item->unit_total_cogs),
                                                    ])
                                                    ->all();
                                            })
                                            ->native(false)
                                            ->searchable(false)
                                            ->multiple()
                                            ->columnSpan(4)
                                            ->required(fn(Get $get): bool => $get('method') == 'manual')
                                            ->live()
                                            ->disabled(fn(Get $get): bool => $get('method') == 'system')
                                            ->afterStateUpdated(function (Set $set, Get $get) use ($quantity, $variantId, $unitPrice, $currencyCode) {
                                                app(CogsAllocationService::class)->updateCogsAllocations(
                                                    $set,
                                                    $get,
                                                    quantity: $quantity,
                                                    variantId: $variantId,
                                                    unitPrice: $unitPrice,
                                                    currencyCode: $currencyCode
                                                );
                                            }),
                                    ])->columns(5),
                                TextEntry::make('allocations_summery')
                                    ->state(function (Get $get) use ($variantId, $quantity, $unitPrice, $currencyCode): View {
                                        $selectedStockIds = $get('stock_items') ?? [];
                                        // dd($selectedAllocations);
                        
                                        $allocatedItems = app(CogsAllocationService::class)->calculate(
                                            selectedStockIds: $selectedStockIds,
                                            variantId: $variantId,
                                            quantityRequired: $quantity,
                                            unitPrice: $unitPrice,
                                            currencyCode: $currencyCode,
                                            method: $get('method')
                                        );
                                        // dd($allocatedItems);
                                        return view(
                                            'livewire.stock-items-summary',
                                            [
                                                'items' => $allocatedItems['stock_items'],
                                                'quantity_required' => $allocatedItems['quantity_required'],
                                                'quantity_taken' => $allocatedItems['quantity_taken'],
                                                'remaining' => $allocatedItems['remaining'],
                                                'total_cogs' => $allocatedItems['total_cogs'],
                                                'total_profit' => $allocatedItems['total_profit']
                                            ]
                                        );
                                    })


                            ];
                        })
                        ->action(function (array $data, Repeater $component, array $arguments) {
                            $itemData = $component->getItemState($arguments['item']);
                            $state = $component->getState();

                            $state[$arguments['item']] = [
                                ...$itemData,
                                'cogs_justification' => $data['cogs_allocations'],
                            ];

                            // Set the new data for the repeater
                            $component->state($state);
                        }),
                ])


        ];
    }
}
