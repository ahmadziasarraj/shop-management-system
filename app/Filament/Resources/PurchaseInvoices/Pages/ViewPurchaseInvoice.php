<?php

namespace App\Filament\Resources\PurchaseInvoices\Pages;

use App\Actions\Inventory\CreateLandedCostAction;
use App\Actions\Inventory\PostLandedCostAction;
use App\Actions\Payments\SupplierPaymentAction;
use App\Actions\Purchases\PostPurchaseReceiptAction;
use App\Actions\Purchases\ReceivePurchaseAction;
use App\Enums\PartyType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseInvoiceStatus;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Models\Currency;
use App\Models\LandedCost;
use App\Models\PurchaseInvoice;
use App\Models\Stock;
use App\Services\Inventory\StockItemInsertService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\JsContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function PHPSTORM_META\map;

class ViewPurchaseInvoice extends ViewRecord
{
    protected static string $resource = PurchaseInvoiceResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->status === PurchaseInvoiceStatus::Draft && request()->boolean('open_approve')) {
            $this->dispatch('open-approve-modal');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn(Model $record): bool => $record->status == PurchaseInvoiceStatus::Draft),
            Action::make('approve')
                ->label('Add Purchase Receipt')
                ->extraAttributes([
                    'x-on:open-approve-modal.window' => '$wire.mountAction(\'approve\')',
                ])
                ->fillForm(function (PurchaseInvoice $record): array {
                    $lines = $record->lines->loadMissing('productVariant.unitMeasurement');

                    return [
                        'purchased_products' => $lines->map(function ($line) {
                            return [
                                'purchase_invoice_line_id' => $line->id,
                                'product_variant_id'       => $line->product_variant_id,
                                'product_variant_name'     => $line->productVariant->name,
                                'unit_measurement'         => $line->productVariant->unitMeasurement?->symbol,
                                'quantity'                 => (int) $line->quantity,
                                'received_quantity'        => (int) ($line->received_quantity ?? 0),
                                'receiving_quantity'       => 0,
                            ];
                        })->values()->all(),

                        'landed_costs' => [
                            [
                                'party_id'          => null,
                                'currency_id'       => null,
                                'payment_method'    => null,
                                'hawala_office_id'  => null,
                                'description'       => null,
                                'equal_allocation' => false,
                                'total_amount'      => 0.0,

                                'landed_cost_allocations' => $lines
                                    ->map(function ($line) {
                                        return [
                                            'purchase_invoice_line_id' => $line->id,
                                            'product_variant_id'       => $line->product_variant_id,
                                            'product_variant_name'     => $line->productVariant->name,
                                            'unit_measurement'         => $line->productVariant->unitMeasurement?->symbol,
                                            'quantity'                 => (int) $line->quantity,
                                            'allocated_amount'         => 0,
                                        ];
                                    })
                                    ->values()
                                    ->all(),
                            ],
                        ],
                    ];
                })
                ->steps([
                    Step::make('grn')
                        ->label('GRN')
                        ->description('Please indecate the numbre of PRODUCTS you have received!')
                        ->schema([...static::grnFormFeilds()])->columns(1)
                        ->afterValidation(
                            function (Action $action, Get $get, Set $set, PurchaseInvoice $record) {
                                if (true) {
                                    $lines = $record->lines->loadMissing('productVariant.unitMeasurement');
                                    $receiptLines = collect($get('purchased_products'));

                                    $landedCosts =  [
                                        [
                                            'party_id'          => null,
                                            'currency_id'       => null,
                                            'payment_method'    => null,
                                            'hawala_office_id'  => null,
                                            'description'       => null,
                                            'equal_allocation' => false,
                                            'total_amount'      => 0.0,

                                            'landed_cost_allocations' => $lines
                                                ->map(function ($line) use ($receiptLines) {
                                                    $receiptLine = $receiptLines->where('purchase_invoice_line_id', $line->id)->first();
                                                    return [
                                                        'purchase_invoice_line_id' => $line->id,
                                                        'product_variant_id'       => $line->product_variant_id,
                                                        'product_variant_name'     => $line->productVariant->name,
                                                        'unit_measurement'         => $line->productVariant->unitMeasurement?->symbol,
                                                        'quantity'                 => (float) $receiptLine['receiving_quantity'],
                                                        'allocated_amount'         => 0,
                                                    ];
                                                })
                                                ->values()
                                                ->all(),
                                        ],
                                    ];
                                    $set('landed_costs', $landedCosts);
                                }
                            },
                        ),
                    Step::make('landed_costs')
                        ->label('Landed Costs')
                        ->description('Add some extra COSTS to received Products')
                        ->schema([...static::landedCostFormFeilds()]),
                ])
                ->action(function (PurchaseInvoice $invoice, array $data) {
                    $invoice = PurchaseInvoice::where('id', $invoice->id)->first();
                    DB::transaction(function () use ($invoice, $data) {

                        // GRN
                        $receiptInvoice = app(ReceivePurchaseAction::class)
                            ->execute(
                                $invoice,
                                $data['purchased_products'],
                                $data['stock_id']
                            );

                        app(PostPurchaseReceiptAction::class)->execute($receiptInvoice);

                        // Landed costs && Stock Items
                        $landedCosts = collect();
                        if (is_array($data['landed_costs']) && count($data['landed_costs']) > 0) {
                            $landedCosts = app(CreateLandedCostAction::class)->execute($data['landed_costs'], $invoice);

                            $landedCosts->each(function (LandedCost $landedCost) {
                                app(PostLandedCostAction::class)->execute($landedCost);
                            });

                            app(StockItemInsertService::class)->upsertWithLandedCosts(landedcosts: $landedCosts, stockId: $data['stock_id']);
                        } else {
                            app(StockItemInsertService::class)->execute(invoice: $invoice, receivedProducts: $data['purchased_products'], stockId: $data['stock_id']);
                        }
                        // Payments && Settlements
                        app(SupplierPaymentAction::class)->purchaseInvoicePayment(invoice: $invoice, landedCosts: $landedCosts);
                    });
                })
                ->visible(function (PurchaseInvoice $invoice): bool {
                    return $invoice->lines->some(fn($line): bool => $line->quantity - $line->received_quantity);
                }),
        ];
    }

    public static function landedCostFormFeilds(): array
    {
        return [
            Repeater::make('landed_costs')
                ->hiddenLabel()
                ->compact()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Select::make('party_id')
                        ->relationship(
                            name: 'party',
                            titleAttribute: 'name',
                        )
                        ->searchable(['name'])
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(2),
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
                        ->columnSpan(2)
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
                    TextInput::make('description')->required()->columnSpan(2),
                    Checkbox::make('equal_allocation')
                        ->inline(false)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $allocations = collect($get('landed_cost_allocations'));

                            $newAllocations = $allocations->map(fn($allocation) => [
                                ...$allocation,
                                'allocated_amount' => 0.0
                            ])->toArray();

                            $set('landed_cost_allocations', $newAllocations);
                            $set('total_amount', 0.0);
                            // dd($totalAmount, $allocations, 'newAllocation--->', $newAllocations, $allocationAmount, $allocations->count());
                        }),
                    TextInput::make('total_amount')
                        ->visible(fn(Get $get): bool => $get('equal_allocation'))
                        ->required()
                        ->numeric()
                        ->default(0.0)
                        ->minValue(0.0)
                        ->live(onBlur: true)
                        ->suffix(JsContent::make(
                            <<<'JS'
                                    $get('currency_code') ? $get('currency_code') : 'USD'
                                    JS
                        ))->afterStateUpdated(function ($state, Get $get, Set $set) {
                            $totalAmount = (float) $state;
                            $allocations = collect($get('landed_cost_allocations'));
                            $totalQuantity = $allocations->sum(fn($item) => $item['quantity']);
                            $allocationAmount = $totalQuantity > 0 ? round($totalAmount / $totalQuantity, 2) : 0;
                            $newAllocations = $allocations->map(function ($allocation) use ($allocationAmount) {
                                return [
                                    ...$allocation,
                                    'allocated_amount' => $allocationAmount * $allocation['quantity']
                                ];
                            })->toArray();

                            $set('landed_cost_allocations', $newAllocations);
                            // dd($totalAmount, $allocations, 'newAllocation--->', $newAllocations, $allocationAmount, $allocations->count());
                        }),
                    Repeater::make('landed_cost_allocations')
                        ->addable(false)
                        ->deletable(false)
                        ->table([
                            TableColumn::make('Product')->width(400),
                            TableColumn::make('Receiving Quantity')->width(200),
                            TableColumn::make('Total Allocated Amount')->width(200),
                        ])
                        ->reorderable(false)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('product_variant_name')->disabled(),
                            Hidden::make('product_variant_id'),
                            Hidden::make('purchase_invoice_line_id'),
                            Hidden::make('unit_measurement')->disabled(),
                            TextInput::make('quantity')->disabled()->dehydrated(true)->suffix(fn(Get $get) => $get('unit_measurement')),
                            TextInput::make('allocated_amount')
                                ->disabled(fn(Get $get): bool => $get('../../equal_allocation'))
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
                        ])
                ])->addAction(function (Action $action): Action {
                    return $action->action(function (Get $get, Set $set, PurchaseInvoice $record) {
                        if (empty($get('landed_costs'))) {
                            $lines = $record->lines->loadMissing('productVariant.unitMeasurement');
                            $receiptLines = collect($get('purchased_products'));

                            $landedCosts =  [
                                [
                                    'party_id'          => null,
                                    'currency_id'       => null,
                                    'payment_method'    => null,
                                    'hawala_office_id'  => null,
                                    'description'       => null,
                                    'equal_allocation' => false,
                                    'total_amount'      => 0.0,

                                    'landed_cost_allocations' => $lines
                                        ->map(function ($line) use ($receiptLines) {
                                            $receiptLine = $receiptLines->where('purchase_invoice_line_id', $line->id)->first();

                                            return [
                                                'purchase_invoice_line_id' => $line->id,
                                                'product_variant_id'       => $line->product_variant_id,
                                                'product_variant_name'     => $line->productVariant->name,
                                                'unit_measurement'         => $line->productVariant->unitMeasurement?->symbol,
                                                'quantity'                 => (float) $receiptLine['receiving_quantity'],
                                                'allocated_amount'         => 0,
                                            ];
                                        })
                                        ->values()
                                        ->all(),
                                ],
                            ];
                        } else {
                            $landedCosts = $get('landed_costs');
                            $firstLandedCost = reset($landedCosts);
                            $firstAllocations = $firstLandedCost['landed_cost_allocations'] ?? [];

                            $newAllocations = collect($firstAllocations)
                                ->mapWithKeys(function (array $allocation) {
                                    return [
                                        (string) Str::uuid() => [
                                            ...$allocation,
                                            'allocated_amount' => 0,
                                        ],
                                    ];
                                })
                                ->all();

                            $landedCosts[(string) Str::uuid()] = [
                                'party_id' => '',
                                'currency_id' => '',
                                'payment_method' => '',
                                'hawala_office_id' => '',
                                'description' => '',
                                'equal_allocation' => false,
                                'total_amount' => 0.0,
                                'landed_cost_allocations' => $newAllocations,
                            ];
                        }

                        $set('landed_costs', $landedCosts);
                    });
                })
        ];
    }

    public static function grnFormFeilds(): array
    {
        return [
            Section::make()->schema([
                Select::make('stock_id')
                    ->relationship('stock', 'name')
                    ->searchable()
                    ->native(false)
                    ->columnSpan(4)
                    ->options(fn(): array => Stock::where('is_active', true)->pluck('name', 'id')->all())
                    ->getSearchResultsUsing(fn(string $search): array => Stock::query()
                        ->where('name', 'like', "%{$search}%")
                        ->limit(15)
                        ->pluck('name', 'id')
                        ->all())
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($livewire, $component) {
                                $livewire->validateOnly($component->getStatePath());
                            }),
                        TextInput::make('address'),
                    ])->createOptionUsing(function (array $data): string {
                        $stock = Stock::create($data);
                        return $stock->getKey();
                    }),
                Checkbox::make('all_received')
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(function (bool $state, Set $set, Get $get) {
                        if ($state) {
                            $products = $get('purchased_products');
                            $updatedProducts = collect($products)->map(function ($product): array {
                                return [
                                    ...$product,
                                    'receiving_quantity' => (int) $product['quantity'] - (int) $product['received_quantity'],
                                ];
                            })->toArray();
                            $set('purchased_products', $updatedProducts);
                        }
                    }),
                Repeater::make('purchased_products')
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->table([
                        TableColumn::make('Product')->width(400),
                        TableColumn::make('Purchased Quantity')->width(200),
                        TableColumn::make('Received Quantity')->width(200),
                        TableColumn::make('Receiving Quantity')->width(200),
                    ])
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('product_variant_name')->disabled(),
                        Hidden::make('product_variant_id'),
                        Hidden::make('purchase_invoice_line_id'),
                        Hidden::make('unit_measurement')->disabled(),
                        TextInput::make('quantity')->disabled()->dehydrated(false)->suffix(fn(Get $get) => $get('unit_measurement')),
                        TextInput::make('received_quantity')->disabled()->dehydrated(false)->suffix(fn(Get $get) => $get('unit_measurement')),
                        TextInput::make('receiving_quantity')
                            ->autofocus()
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->maxValue(function (Get $get): float {
                                $quantity = (float) ($get('quantity') ?? 0);
                                $received = (float) ($get('received_quantity') ?? 0);

                                return max(0, $quantity - $received);
                            })
                            ->suffix(fn(Get $get) => $get('unit_measurement'))
                            ->live(onBlur: true)
                            ->validationMessages([
                                'max' => 'You cannot receive more than the remaining quantity.',
                            ])
                            ->live(onBlur: true)->suffix(fn(Get $get) => $get('unit_measurement')),
                    ])
            ])->columns(5)
        ];
    }
}
