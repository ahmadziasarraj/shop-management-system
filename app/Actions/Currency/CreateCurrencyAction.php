<?php

namespace App\Actions\Currency;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;

final class CreateCurrencyAction
{
    public function execute(
        array $data,
        ?Currency $currency = null
    ): Currency {
        $data['code'] = strtoupper(trim($data['code']));
        $data['name'] = trim($data['name']);

        return DB::transaction(function () use ($data, $currency) {

            if (($data['is_base'] ?? false) === true) {
                Currency::query()
                    ->where('id', '!=', $currency?->id)
                    ->update([
                        'is_base' => false,
                    ]);
            }

            if ($currency) {
                $currency->update($data);

                return $currency->refresh();
            }


            return Currency::create($data);
        });
    }
}
