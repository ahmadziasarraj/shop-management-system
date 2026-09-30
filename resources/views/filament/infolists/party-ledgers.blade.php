<div>

    <livewire:party-ledgers
        :party="$party"
        :currency="$baseCurrency"
        :is-all=true
        :base-currency-code="$baseCurrency->code"
        :key="'party-ledger-' . $baseCurrency->id"
    />

    @foreach ($currenciesList as $currency)
        <livewire:party-ledgers
            :party="$party"
            :currency="$currency"
            :is-all=false
            :base-currency-code="$baseCurrency->code"
            :key="'party-ledgers-' . $currency->id"
        />
    @endforeach

</div>
