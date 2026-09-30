<?php

namespace App\Filament\Resources\ProductVariants\Pages;

use App\Filament\Resources\ProductVariants\ProductVariantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
             CreateAction::make()->using(function (array $data, string $model): Model {
                $data['sku'] = 'VAR-' . now()->format('YmdHis'). '-' . strtoupper(Str::random(5));
                return $model::create($data);
            }),
        ];
    }
}
