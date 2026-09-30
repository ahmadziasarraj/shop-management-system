<?php

namespace App\Filament\Resources\Parties\Schemas;

use App\Models\Currency;
use App\Models\Party;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class PartyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('type')->badge(),
                IconEntry::make('is_active')->boolean(),
                TextEntry::make('email'),
                TextEntry::make('phone')->bulleted(),
                TextEntry::make('Ledger')
                    ->columnSpanFull()
                    ->state(function (Party $party): View {
                        $baseCurrency = Currency::where('is_base', true)->first();
                        $currenciesList = $party->currenciesInLedgerEntries;

                        return view(
                            'filament.infolists.party-ledgers',
                            [
                                'party' => $party,
                                'baseCurrency' => $baseCurrency,
                                'currenciesList' => $currenciesList
                            ]
                        );
                    })
                    ->html()->hiddenLabel(),
            ])->columns(4);
    }
}
