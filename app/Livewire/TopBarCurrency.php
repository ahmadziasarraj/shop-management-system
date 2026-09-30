<?php

namespace App\Livewire;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Collection;
use Livewire\Component;

class TopBarCurrency extends Component
{
    public Currency $baseCurrency;
    public Collection $rates;

    public function mount()
    {
        $this->baseCurrency = Currency::where('is_base', true)->first();

        $rates = ExchangeRate::latestForPairs($this->baseCurrency->id);

        $this->rates = $rates->map(function ($rate) {
            return [
                'from' => $rate->fromCurrency->code,
                'to' => $rate->toCurrency->code,
                'rate' => round( $rate->rate, 2),
            ];
        })->sortBy('to');
    }

    public function render()
    {

        return view('livewire.top-bar-currency');
    }
}
