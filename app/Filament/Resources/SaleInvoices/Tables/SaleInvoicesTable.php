<?php

namespace App\Filament\Resources\SaleInvoices\Tables;

use App\Models\SaleInvoice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaleInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('party.name')
                    ->searchable(),
                TextColumn::make('hawalaOffice.name')
                    ->searchable(),
                TextColumn::make('system_invoice_number')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money(fn(SaleInvoice $reocrd) => $reocrd->currency->code)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
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
