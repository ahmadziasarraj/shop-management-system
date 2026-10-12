<?php

namespace App\Filament\Resources\SaleInvoices;

use App\Filament\Resources\SaleInvoices\Pages\CreateSaleInvoice;
use App\Filament\Resources\SaleInvoices\Pages\EditSaleInvoice;
use App\Filament\Resources\SaleInvoices\Pages\ListSaleInvoices;
use App\Filament\Resources\SaleInvoices\Pages\ViewSaleInvoice;
use App\Filament\Resources\SaleInvoices\Schemas\SaleInvoiceForm;
use App\Filament\Resources\SaleInvoices\Schemas\SaleInvoiceInfolist;
use App\Filament\Resources\SaleInvoices\Tables\SaleInvoicesTable;
use App\Models\SaleInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SaleInvoiceResource extends Resource
{
    protected static ?string $model = SaleInvoice::class;
    protected static string|UnitEnum|null $navigationGroup = 'Sales';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?string $recordTitleAttribute = 'SaleInvoice';

    public static function form(Schema $schema): Schema
    {
        return SaleInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaleInvoicesTable::configure($table);
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
            'index' => ListSaleInvoices::route('/'),
            'create' => CreateSaleInvoice::route('/create'),
            'view' => ViewSaleInvoice::route('/{record}'),
            'edit' => EditSaleInvoice::route('/{record}/edit'),
        ];
    }
}
