<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Enums\PartyType;
use App\Enums\PaymentMethod;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Purchases\PurchaseInvoiceFinancialSummary;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\JsContent;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class PurchaseInvoiceForm
{
    /**
     * These should show at the end of form
     * subtotal
     * total_amount
     * base_paid_amount
     * base_subtotal
     * base_discount_amount
     * base_total_amount
     * discount_amount
     * paid_amount
     */

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
                                modifyQueryUsing: fn(Builder $query) => $query->whereIn('type', [PartyType::Vendor, PartyType::VendorCustomer])
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
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
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
                        TextInput::make('original_invoice_number')
                            ->required()
                            ->label('Invoice Number')
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($livewire, $component) {
                                $livewire->validateOnly($component->getStatePath());
                            }),
                        DatePicker::make('date')
                            ->required()
                            ->native(false)
                            ->firstDayOfWeek(7)
                            ->label('Purchase Date'),
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
                    ])->columnSpanFull()->columns(4),
                Fieldset::make('Purchased Products')
                    ->schema([
                        Repeater::make('purchased_products')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Product')->width(400),
                                TableColumn::make('Quantity')->width(200),
                                TableColumn::make('Unit Cost')->width(200),
                                TableColumn::make('Total Cost')->width(200),
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
                                        $products = Product::with('variants')->get();

                                        $result = $products->mapWithKeys(fn($product) => [
                                            $product->name => $product->variants->pluck('name', 'id')->toArray(),
                                        ])->toArray();
                                        return $result;
                                    })
                                    ->getSearchResultsUsing(fn(string $search): array => ProductVariant::query()
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('sku', 'like', "%{$search}%")
                                        ->limit(15)
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            $product = ProductVariant::where('id', $state)->first();
                                            $set('unit_measurement', $product->unitMeasurement->symbol);
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Hidden::make('unit_measurement')->live()->default(''),
                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $total = $get('unit_cost') * $state;
                                        $set('total_cost', $total);
                                    })
                                    ->suffix(JsContent::make(
                                        <<<'JS'
                                        $get('unit_measurement') ? $get('unit_measurement') : ''
                                        JS
                                    ))
                                    ->placeholder('quantity'),
                                TextInput::make('unit_cost')
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
                                        $set('total_cost', $total);
                                    })
                                    ->placeholder('unit cost'),
                                TextInput::make('total_cost')
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
                TextEntry::make('summary')
                    ->columnSpanFull()
                    ->state(function (Get $get): View {

                        $summary = app(PurchaseInvoiceFinancialSummary::class)->calculate(
                            products: $get('purchased_products') ?? [],
                            discountAmount: (float) ($get('discount_amount') ?? 0),
                            paidAmount: (float) ($get('paid_amount') ?? 0),
                            currencyCode: $get('currency_code'),
                        );
                        return view(
                            'livewire.purchase-invoice-financial-summary',
                            [
                                'amounts' => $summary->get('amounts'),
                                'baseAmounts' => $summary->get('baseAmounts'),
                                'currencyCode' => $summary->get('currencyCode'),
                                'baseCurrencyCode' => $summary->get('baseCurrencyCode'),
                                'rate' => number_format($summary->get('rate'), 2),
                                'paymentStatus' => $summary->get('paymentStatus'),
                            ],
                        );
                    })
                    ->html()->hiddenLabel()
            ]);
    }
}
