{{-- <div class="flex items-center gap-4" wire:poll.60s>
    <div class="text-center ">
        <div class="text-xs font-semibold text-gray-950 dark:text-white">
            {{ now()->format('d M Y') }}
        </div>
        <div x-data="{ time: '{{ now()->format('H:i:s') }}' }" x-init="setInterval(() => {
            time = new Date().toLocaleTimeString('en-GB');
        }, 1000)" x-text="time"
            class="text-xs text-gray-500 dark:text-gray-400"></div>
    </div>

    <div class="flex gap-2">
        <div class="rounded-lg bg-gray-100 px-2.5 py-1.5 dark:bg-gray-800 flex items-center gap-2">
            @foreach ($this->rates as $rate)
                <div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        <span class="ml-1 text-sm font-semibold text-gray-950 dark:text-white">
                            1
                        </span> {{ $rate['from'] }} = <span class="ml-1 text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $rate['rate'] }}
                        </span> {{ $rate['to'] }}
                    </span>
                </div>
                @unless ($loop->last)
                    <span class="text-sm  text-gray-400">|</span>
                @endunless
            @endforeach
        </div>
    </div>
</div> --}}

<div class="flex gap-2">
    <div class="rounded-lg bg-gray-100 px-2.5 py-1.5 dark:bg-gray-800 flex items-center gap-2">
        @foreach ($this->rates as $rate)
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $rate['from'] }} -> {{ $rate['to'] }}
                </span>
                <span class="ml-1 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $rate['rate'] }}
                </span>
            </div>
            @unless ($loop->last)
                <span class="text-sm  text-gray-400">|</span>
            @endunless
        @endforeach
    </div>
</div>
