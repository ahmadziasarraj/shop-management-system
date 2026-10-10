<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Models\Payment;
use App\Services\Helpers\EnumToString;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('party.name')
                    ->searchable(),
                TextColumn::make('payment_number')
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn($state): string => app(EnumToString::class)->convert($state)),
                TextColumn::make('amount')
                    ->money(fn(Payment $reocrd) => $reocrd->currency->code)
                    ->sortable(),
                TextColumn::make('base_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('exchange_rate')
                    ->money(fn(Payment $reocrd) => $reocrd->currency->code)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
