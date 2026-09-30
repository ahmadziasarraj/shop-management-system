<?php

namespace App\Filament\Resources\Parties\Tables;

use App\Models\Party;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class PartiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('financial_summary')
                    ->state(fn(Party $record): HtmlString => self::getFinancialSummary($record))
                    ->html(),
                // ->formatStateUsing(fn(Model $record): HtmlString => self::getFinancialSummary($record)),
                TextColumn::make('limit')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('opening_receivable')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('opening_payable')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            // ->recordClasses(fn(Party $record) => match (true) {
            //     $record->total_debit - $record->total_credit > 0 => 'bg-warning-200 dark:bg-warning-800/30 text-warning-800 dark:text-warning-200',
            //     $record->total_debit - $record->total_credit < 0 => 'bg-danger-300 dark:bg-danger-800/30 text-danger-800 dark:text-danger-200',
            //     default => 'bg-success-300 dark:bg-success-800/30 text-success-800 dark:text-success-200',
            // })
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function getFinancialSummary(Party $record): HtmlString
    {

        $totalDebit = (float) ($record->ledgerEntries->sum(fn($ledger) => $ledger->base_debit_amount) ?? 0);
        $totalCredit = (float) ($record->ledgerEntries->sum(fn($ledger) => $ledger->base_credit_amount) ?? 0);

        /*
        * Party balance:
        *
        * Debit > Credit => بدهکار
        * Credit > Debit => بستانکار
        * Debit = Credit => صفر
        */

        $balance = $totalCredit - $totalDebit;

        $balanceLabel = [
            'label' => 'صفر',
            'bgColor' => Color::convertToRgb(Color::Green[500])
        ];
        if ($balance > 0) {
            $balanceLabel = [
                'label' => 'بستانکار',
                'bgColor' => Color::convertToRgb(Color::Orange[400])
            ];
        } elseif ($balance < 0) {
            $balanceLabel = [
                'label' => 'بدهکار',
                'bgColor' => Color::convertToRgb(Color::Red[400])
            ];
        }
        // dd($balanceLabel['bgColor']);

        return new HtmlString('
            <div class="flex gap-4 w-full">
                <div>
                    <span>بدهکار:</span> <span>' . Number::currency($totalDebit) . '</span>
                </div>
                <div>
                    <span>بستانکار:</span> <span>' . Number::currency($totalCredit) . '</span>
                </div>
            </div>
            <div class="px-3 py-0.5">
                <span>بیلانس:</span> <span class="px-3">' . Number::currency( $balance) . '</span>
                <span class="text-xs rounded p-0.5 px-2" style="background-color:' . $balanceLabel['bgColor'] . '">' . $balanceLabel['label'] . '</span>
            </div>
        ');
    }
}
