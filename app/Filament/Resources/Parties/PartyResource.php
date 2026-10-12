<?php

namespace App\Filament\Resources\Parties;

use App\Filament\Resources\Parties\Pages\ListParties;
use App\Filament\Resources\Parties\Pages\ViewParty;
use App\Filament\Resources\Parties\Schemas\PartyForm;
use App\Filament\Resources\Parties\Schemas\PartyInfolist;
use App\Filament\Resources\Parties\Tables\PartiesTable;
use App\Models\Party;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PartyResource extends Resource
{
    protected static ?string $model = Party::class;

    protected static string|UnitEnum|null $navigationGroup = 'Parties';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'Party';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('ledgerEntries as total_debit', 'base_debit_amount')
            ->withSum('ledgerEntries as total_credit', 'base_credit_amount');
    }

    public static function form(Schema $schema): Schema
    {
        return PartyForm::configure($schema);
    }
    public static function infolist(Schema $schema): Schema
    {
        return PartyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParties::route('/'),
            'view' => ViewParty::route('/{record}'),
            // 'create' => CreateParty::route('/create'),
            // 'edit' => EditParty::route('/{record}/edit'),
        ];
    }
}
