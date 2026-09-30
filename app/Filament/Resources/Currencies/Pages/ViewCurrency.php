<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Actions\Currency\CreateCurrencyAction;
use App\Filament\Resources\Currencies\CurrencyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCurrency extends ViewRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->action(function (array $data): void {
                app(CreateCurrencyAction::class)->execute($data);
            }),
        ];
    }
}
