<?php

namespace App\Services\Accounting;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

final class ExchangeRateService
{
    public function getRate(
        string $fromCurrencyCode,
        string $toCurrencyCode,
        ?string $baseCurrencyCode = 'USD',
        ?Carbon $date = null,
    ): float {

        $from = strtoupper($fromCurrencyCode);
        $to = strtoupper($toCurrencyCode);
        $base = strtoupper($baseCurrencyCode);

        if ($from === $to) {
            return 1.0;
        }

        $date ??= now();


        /*
         * Direct rate.
         */
        $direct = $this->findDirectRate(
            $from,
            $to,
            $date
        );

        if ($direct !== null) {
            return $direct;
        }

        /*
         * Inverse rate.
         */
        $inverse = $this->findDirectRate(
            $to,
            $from,
            $date
        );

        if ($inverse !== null) {
            return 1 / $inverse;
        }

        /*
         * Base currency.
         */
        if (
            $from !== $base &&
            $to !== $base
        ) {

            $fromToBase = $this->getRate(
                $from,
                $base,
                $date
            );

            $baseToTarget = $this->getRate(
                $base,
                $to,
                $date
            );

            return $fromToBase * $baseToTarget;
        }

        throw new RuntimeException(
            "Exchange rate not configured for "
                . "{$from} → {$to}."
        );
    }

    public function convert(
        float $amount,
        string $fromCurrencyCode,
        string $toCurrencyCode,
        ?Carbon $date = null,
    ): float {

        $rate = $this->getRate(
            $fromCurrencyCode,
            $toCurrencyCode,
            $date
        );

        return $amount * $rate;
    }

    public function convertMany(
        Collection $amounts,
        string $fromCurrency,
        string $toCurrency,
        ?Carbon $date = null,
    ): Collection {
        if ($fromCurrency === $toCurrency) {
            return collect($amounts);
        }

        $rate = $this->getRate(
            fromCurrencyCode: $fromCurrency,
            toCurrencyCode: $toCurrency,
            date: $date
        );

        return $amounts->map(fn(float|int $amount) => $amount * $rate);
    }

    private function findDirectRate(
        string $from,
        string $to,
        Carbon $date,
    ): ?float {

        return ExchangeRate::query()
            ->whereHas(
                'fromCurrency',
                fn($query) =>
                $query->where('code', $from)
            )
            ->whereHas(
                'toCurrency',
                fn($query) =>
                $query->where('code', $to)
            )
            ->where(
                'effective_at',
                '<=',
                $date
            )
            ->orderByDesc(
                'effective_at'
            )
            ->value('rate');

        // $key = sprintf(
        //     'exchange-rate:%s:%s:%s',
        //     $from,
        //     $to,
        //     $date->toDateString()
        // );

        // return Cache::remember(
        //     $key,
        //     now()->addMinutes(1),
        //     function () use (
        //         $from,
        //         $to,
        //         $date
        //     ) {

        //         return ExchangeRate::query()
        //             ->whereHas(
        //                 'fromCurrency',
        //                 fn($query) =>
        //                 $query->where('code', $from)
        //             )
        //             ->whereHas(
        //                 'toCurrency',
        //                 fn($query) =>
        //                 $query->where('code', $to)
        //             )
        //             ->where(
        //                 'effective_at',
        //                 '<=',
        //                 $date
        //             )
        //             ->orderByDesc(
        //                 'effective_at'
        //             )
        //             ->value('rate');
        //     }
        // );
    }
}
