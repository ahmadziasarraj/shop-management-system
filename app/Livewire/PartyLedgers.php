<?php

namespace App\Livewire;

use App\Models\Currency;
use App\Models\Party;
use App\Models\PartyLedgerEntry;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Livewire\Component;

class PartyLedgers extends Component implements HasTable, HasSchemas, HasActions
{
    use InteractsWithTable;
    use InteractsWithSchemas;
    use InteractsWithActions;

    public Party $party;
    public Currency $currency;
    public bool $isAll;
    public string $baseCurrencyCode;

    public float $totalDebit = 0;
    public float $totalCredit = 0;
    public float $totalBaseDebit = 0;
    public float $totalBaseCredit = 0;
    public float $balance = 0;
    public float $baseBalance = 0;
    public string $status;


    private function ledgerQuery(): Builder
    {
        return PartyLedgerEntry::query()
            ->where('party_id', $this->party->id)
            ->when(
                ! $this->isAll,
                fn(Builder $query) => $query->where('currency_id', $this->currency->id)
            );
    }

    public function mount(
        Party $party,
        Currency $currency,
        bool $isAll,
        string $baseCurrencyCode
    ): void {
        $this->party = $party;
        $this->currency = $currency;
        $this->isAll = $isAll;
        $this->baseCurrencyCode = $baseCurrencyCode;

        $query = $this->ledgerQuery();

        $this->totalDebit = (float) $query->sum('debit_amount');
        $this->totalCredit = (float) $query->sum('credit_amount');
        $this->totalBaseDebit = (float) $query->sum('base_debit_amount');
        $this->totalBaseCredit = (float) $query->sum('base_credit_amount');

        $this->balance = $this->totalCredit - $this->totalDebit;
        $this->baseBalance = $this->totalBaseCredit - $this->totalBaseDebit;

        $this->status = match (true) {
            $this->balance > 0 => 'Credit',
            $this->balance < 0 => 'Debit',
            default      => 'Settled',
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->ledgerQuery())
            ->toolbarActions([
                BulkAction::make('delete')
                    ->requiresConfirmation()
                    ->action(fn(Collection $records) => $records->each->delete()),
                BulkActionGroup::make([
                    BulkAction::make('forceDelete')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => $records->each->forceDelete()),
                ]),
            ])
            // ->searchable(false)
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    // ->searchable(isIndividual: true)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->hidden($this->isAll)
                    ->formatStateUsing(function ($state) {
                        return (float)$state == 0 ? '-' : Number::currency($state, $this->currency->code);
                    })
                    ->alignEnd(),

                TextColumn::make('credit_amount')
                    ->label('Credit')
                    ->hidden($this->isAll)
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return (float)$state == 0 ? '-' : Number::currency($state, $this->currency->code);
                    })
                    ->sortable(),
                TextColumn::make('base_debit_amount')
                    ->label('Base Debit')
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return (float)$state == 0 ? '-' : Number::currency($state, $this->baseCurrencyCode);
                    })
                    ->toggleable(condition: !$this->isAll, isToggledHiddenByDefault: !$this->isAll)
                    ->sortable(),

                TextColumn::make('base_credit_amount')
                    ->label('Base Credit')
                    ->alignEnd()
                    ->formatStateUsing(function ($state) {
                        return (float)$state == 0 ? '-' : Number::currency($state, $this->baseCurrencyCode);
                    })
                    ->toggleable(condition: !$this->isAll, isToggledHiddenByDefault: !$this->isAll)
                    ->sortable(),

                // TextColumn::make('balance')
                //     ->numeric(decimalPlaces: 2)
                //     ->alignEnd(),
            ])
            ->heading(function (): View {
                return  view('livewire.table-header', [
                    'name' => $this->party->name,
                    'currencyCode' => $this->currency->code,
                    'baseCurrencyCode' => $this->baseCurrencyCode,
                    'totalDebit' => Number::currency($this->totalDebit, $this->currency->code),
                    'totalCredit' => Number::currency($this->totalCredit, $this->currency->code),
                    'totalBaseDebit' => Number::currency($this->totalBaseDebit, $this->baseCurrencyCode),
                    'totalBaseCredit' => Number::currency($this->totalBaseCredit, $this->baseCurrencyCode),
                    'balance' => Number::currency($this->balance, $this->currency->code),
                    'baseBalance' => Number::currency($this->baseBalance, $this->baseCurrencyCode),
                    'status' => $this->status,
                    'isAll' => $this->isAll
                ]);
            })
            ->deferLoading()
            ->striped()
            ->defaultSort('transaction_date', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function render()
    {
        return view('livewire.party-ledgers');
    }
}
