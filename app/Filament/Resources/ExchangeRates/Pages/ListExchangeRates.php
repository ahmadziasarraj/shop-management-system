<?php

namespace App\Filament\Resources\ExchangeRates\Pages;

use App\Actions\Currency\CreateCurrencyExchangeAction;
use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExchangeRates extends ListRecords
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
             CreateAction::make()->action(function (array $data): void {
                app(CreateCurrencyExchangeAction::class)->execute($data);
            }),
        ];
    }
}
