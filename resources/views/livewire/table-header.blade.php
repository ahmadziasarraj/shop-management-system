<!-- Account Summary -->
<section>
    <div class="overflow-hidden rounded-xl bg-white ">

        <!-- Header -->
        <div
            class="flex flex-col gap-2 border-b border-slate-200 px-6 py-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 10h18M7 15h2m2 0h2m2 0h2M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Account Summary
                        </h2>

                        <p class="text-xs text-slate-500">
                            Financial position of this party
                        </p>
                    </div>
                </div>
            </div>

            <!-- Currency -->
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-medium text-slate-400">
                    Currency:
                </span>

                <span class="font-semibold text-slate-700">
                    {{ $currencyCode }}
                </span>
            </div>
        </div>


        <!-- Main Summary -->
        <div class="p-5 sm:p-6">

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

                <!-- ===================================================== -->
                <!-- Current Balance -->
                <!-- ===================================================== -->

                <div
                    class="relative overflow-hidden rounded-xl border border-indigo-200 bg-indigo-50/50 p-6 lg:col-span-5">

                    <div class="flex items-start justify-between">

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                                Current Balance
                            </p>

                            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
                                @if ($isAll)
                                    {{ $baseBalance }}
                                @else
                                    {{ $balance }}
                                @endif
                            </p>
                        </div>

                        <!-- Balance Status -->
                        <div
                            class="flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-600 shadow-sm ring-1 ring-slate-200/70">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ $status }}
                        </div>

                    </div>

                    <div class="mt-6 border-t border-indigo-100 pt-4">

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">
                                Debit
                            </span>

                            <span class="font-medium text-slate-700">
                                @if ($isAll)
                                    {{ $totalBaseDebit }}
                                @else
                                    {{ $totalDebit }}
                                @endif
                            </span>
                        </div>

                        <div class="mt-2.5 flex items-center justify-between text-xs">
                            <span class="text-slate-500">
                                Credit
                            </span>

                            <span class="font-medium text-slate-700">
                                @if ($isAll)
                                    {{ $totalBaseCredit }}
                                @else
                                    {{ $totalCredit }}
                                @endif
                            </span>
                        </div>

                    </div>
                </div>


                <!-- ===================================================== -->
                <!-- Right Side Metrics -->
                <!-- ================================================= -->

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:col-span-7">

                    <!-- Total Debit -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5">

                        <div class="flex items-center justify-between">

                            <div class="flex items-center gap-2.5">

                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M12 5v14m0 0l-5-5m5 5l5-5" />
                                    </svg>
                                </div>

                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Total Debit
                                </span>

                            </div>

                        </div>

                        <p class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
                            @if ($isAll)
                                {{ $totalBaseDebit }}
                            @else
                                {{ $totalDebit }}
                            @endif
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Total amount debited
                        </p>

                    </div>


                    <!-- Total Credit -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5">

                        <div class="flex items-center gap-2.5">

                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 19V5m0 0l-5 5m5-5l5 5" />
                                </svg>
                            </div>

                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Total Credit
                            </span>

                        </div>

                        <p class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
                            @if ($isAll)
                                {{ $totalBaseCredit }}
                            @else
                                {{ $totalCredit }}
                            @endif
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Total amount credited
                        </p>

                    </div>


                    <!-- Base Balance -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5 sm:col-span-2">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Base Currency Balance
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Balance converted to the system base currency
                                </p>
                            </div>

                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-bold tracking-tight text-slate-900">
                                    {{$baseBalance}}
                                </span>

                                <span class="text-xs font-semibold text-slate-400">
                                    {{$baseCurrencyCode}}
                                </span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</section>
