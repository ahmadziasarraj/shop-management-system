<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Actions\Currency\CreateCurrencyAction;
use App\Filament\Resources\Currencies\CurrencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->action(function (array $data): void {
                app(CreateCurrencyAction::class)->execute($data);
            }),
        ];
    }
}
