<?php

namespace App\Actions\Currency;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreateCurrencyExchangeAction
{
    public function execute(array $data): ExchangeRate {

        $baseCurrency = Currency::query()->where('is_base', true)->first();
        $data['from_currency_id'] = $baseCurrency->id;
        $data['created_by'] = Auth::user()->id;

        return DB::transaction(function () use ($data) {
            return ExchangeRate::create($data);
        });
    }
}
