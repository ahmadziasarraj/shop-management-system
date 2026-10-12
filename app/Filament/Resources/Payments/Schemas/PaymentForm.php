<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PartyType;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Services\Helpers\EnumToString;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('transaction_type')
                    ->options(
                        collect(TransactionType::cases())
                            ->mapWithKeys(fn(TransactionType $type) => [
                                $type->value => sprintf(
                                    '<div>
                                        <div class="font-medium">%s</div>
                                        <div class="text-sm text-gray-500">%s</div>
                                    </div>',
                                    e(app(EnumToString::class)->convert($type)),
                                    e($type->description()),
                                ),
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->live(onBlur: true)
                    ->native(false)
                    ->allowHtml()
                    ->afterStateUpdated(fn(Set $set) => $set('party_id', null)),
                Radio::make('payment_method')
                    ->options(PaymentMethod::class)
                    ->required()
                    ->inline()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('hawala_office_id', null)),
                Select::make('party_id')
                    ->relationship(
                        name: 'party',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (
                            Builder $query,
                            callable $get
                        ) {
                            return match ($get('transaction_type')) {
                                TransactionType::ReceiveFromCustomer->value,
                                TransactionType::RefundToCustomer->value =>
                                $query->where(fn($q) => $q->where('type', PartyType::Customer)->orWhere('type', PartyType::VendorCustomer)),

                                TransactionType::PayVendor->value,
                                TransactionType::ReceiveFromVendor->value =>
                                $query->where(fn($q) => $q->where('type', PartyType::Vendor)->orWhere('type', PartyType::VendorCustomer)),

                                TransactionType::HawalaReceipt->value,
                                TransactionType::HawalaPayment->value =>
                                $query->where('type', PartyType::HawalaOffice),
                                
                                // TransactionType::EmployeePayment->value =>
                                // $query->where('type', PartyType::Employee),

                                // TransactionType::PayOwner->value,
                                // TransactionType::ReceiveFromOwner->value =>
                                // $query->where('type', PartyType::Owner),


                                default => $query,
                            };
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->live(onBlur:true)
                    ->afterStateUpdated(fn(Set $set)=> $set('hawala_office_id', null))
                    ->required(),
                Select::make('hawala_office_id')
                    ->relationship(
                        name: 'hawalaOffice',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query, Get $get) => $query->where('type', PartyType::HawalaOffice)->whereNot('id', $get('party_id'))
                    )
                    ->visible(fn(Get $get): bool => $get('payment_method') == PaymentMethod::HawalaOffice)
                    ->searchable(['name'])
                    ->preload()
                    ->native(false)
                    ->required(),
                DatePicker::make('payment_date')
                    ->required()
                    ->native(false)
                    ->firstDayOfWeek(6)
                    ->label('Date')
                    ->closeOnDateSelection()
                    ->default(now()),
                FusedGroup::make([
                    TextInput::make('amount')
                        ->required()
                        ->numeric()
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
                        ->placeholder('Currency'),
                ])
                    ->label('Amount | Currency')
                    ->columns(3),
                Textarea::make('notes')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
