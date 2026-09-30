<x-filament-widgets::widget>
    <x-filament::section>

        <div class="space-y-5">

            @foreach ($accountData as $account)

                <div class="space-y-3">

                    {{-- Header --}}
                    <div class="flex items-center justify-between">

                        <div>
                            <div class="text-base font-semibold">
                                {{ $account['code'] }}
                                —
                                {{ $account['name'] }}
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ $account['account_type'] }}
                            </div>
                        </div>

                        <div class="text-lg font-bold">
                            {{ number_format($account['balance'], 2) }}
                        </div>

                    </div>

                    {{-- Leaf accounts --}}
                    <div
                        class="divide-y divide-gray-200 rounded-lg border dark:divide-gray-700 dark:border-gray-700"
                    >

                        @foreach ($account['leaves'] as $leaf)

                            <div
                                class="flex items-center justify-between px-4 py-3"
                            >

                                <div class="flex items-center gap-3">

                                    <span
                                        class="text-xs font-medium text-gray-500"
                                    >
                                        {{ $leaf['code'] }}
                                    </span>

                                    <span class="text-sm">
                                        {{ $leaf['name'] }}
                                    </span>

                                </div>

                                <div
                                    @class([
                                        'text-sm font-semibold',
                                        'text-success-600'
                                            => $leaf['balance'] > 0,
                                        'text-danger-600'
                                            => $leaf['balance'] < 0,
                                    ])
                                >
                                    {{ number_format($leaf['balance'], 2) }}
                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>

    </x-filament::section>
</x-filament-widgets::widget>
