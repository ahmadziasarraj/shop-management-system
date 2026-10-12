<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\Payments\CreatePaymentAction;
use App\Data\Payments\PaymentData;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Currency;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreatePaymentAction::class)->execute($data);
    }
}
