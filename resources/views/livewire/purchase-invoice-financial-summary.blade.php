@php
    use Illuminate\Support\Number;
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">
                Financial Summary
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                Purchase invoice payment details
            </p>
        </div>

        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m0 2a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
            </svg>
        </div>
    </div>

    <div class="p-6">

        <!-- Main financial figures -->
        {{-- <div class="grid grid-cols-1 gap-4 sm:grid-cols-3"> --}}

            <!-- Subtotal -->
            {{-- <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-slate-400"></div>
                    <span class="text-sm font-medium text-slate-500">
                        Subtotal
                    </span>
                </div>

                <div class="mt-3">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">
                        {{ Number::currency($amounts->get('subtotal'), $currencyCode) }}
                    </span>
                </div>
            </div> --}}

            <!-- Discount -->
            {{-- <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-5">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-rose-500"></div>
                    <span class="text-sm font-medium text-rose-600">
                        Discount
                    </span>
                </div>

                <div class="mt-3">
                    <span class="text-2xl font-bold tracking-tight text-rose-600">
                        - {{ Number::currency($amounts->get('discount_amount'), $currencyCode) }}
                    </span>
                </div>
            </div> --}}

            <!-- Total -->
            {{-- <div class="rounded-xl bg-slate-900 p-5 text-white shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-emerald-400"></div>
                    <span class="text-sm font-medium text-slate-300">
                        Total Amount
                    </span>
                </div>

                <div class="mt-3">
                    <span class="text-2xl font-bold tracking-tight">
                        {{ Number::currency($amounts->get('total_amount'), $currencyCode) }}
                    </span>
                </div>
            </div> --}}

        {{-- </div> --}}


        <!-- Calculation breakdown -->
        <div class="mt-6">

            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-slate-900"> Accounting Breakdown </h4>
                    <p class="mt-0.5 text-xs text-slate-400"> Converted values used for accounting </p>
                </div>

                <span class="text-xs font-medium uppercase tracking-wider text-slate-400">
                    Base Currency Rate ( <span class="text-slate-700"> {{ $rate }} </span> )
                </span>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200">

                <!-- Base subtotal -->
                <div class="flex items-center justify-between px-5 py-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            Base Subtotal
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Original amount converted to base currency
                        </p>
                    </div>

                    <span class="font-semibold text-slate-900">
                        {{ Number::currency($baseAmounts->get('subtotal'), $baseCurrencyCode) }}
                    </span>
                </div>

                <!-- Base discount -->
                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            Base Discount
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Applied discount
                        </p>
                    </div>

                    <span class="font-semibold text-rose-600">
                        - {{ Number::currency($baseAmounts->get('discount_amount'), $baseCurrencyCode) }}
                    </span>
                </div>

                <!-- Base total -->
                <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4">
                    <span class="text-sm font-semibold text-slate-900">
                        Base Total
                    </span>

                    <span class="text-base font-bold text-slate-900">
                        {{ Number::currency($baseAmounts->get('total_amount'), $baseCurrencyCode) }}
                    </span>
                </div>

            </div>
        </div>


        <!-- Payment section -->
        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">

            <!-- Paid -->
            <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-100"></div>

                <div class="relative">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-emerald-700">
                            Paid Amount
                        </span>

                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            Paid
                        </span>
                    </div>

                    <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-800">
                        {{ Number::currency($amounts->get('paid_amount'), $currencyCode) }}
                    </p>

                    <div class="mt-4 flex items-center justify-between border-t border-emerald-200 pt-3">
                        <span class="text-xs text-emerald-600">
                            Base Paid Amount
                        </span>

                        <span class="text-sm font-semibold text-emerald-800">
                            {{ Number::currency($baseAmounts->get('paid_amount'), $baseCurrencyCode) }}
                        </span>
                    </div>
                </div>
            </div>


            <!-- Balance -->
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500">
                        Payment Overview
                    </span>

                    <span class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Recorded
                    </span>
                </div>

                <div class="mt-5 space-y-1">

                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">
                            Invoice Total
                        </span>
                        <span class="font-medium text-slate-900">
                            {{ Number::currency($amounts->get('total_amount'), $currencyCode) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">
                            Amount Paid
                        </span>
                        <span class="font-medium text-emerald-600">
                            {{ Number::currency($amounts->get('paid_amount'), $currencyCode) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">
                            Balance
                        </span>
                        <span class="font-medium text-slate-900">
                            {{ Number::currency($amounts->get('balance'), $currencyCode) }}
                        </span>
                    </div>

                    <div class="border-t border-slate-200 pt-3">
                        <div class="flex justify-between">
                            <span class="text-sm font-semibold text-slate-700">
                                Payment Status
                            </span>

                            <span class="text-sm font-bold text-emerald-600">
                                {{$paymentStatus}}
                            </span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>
