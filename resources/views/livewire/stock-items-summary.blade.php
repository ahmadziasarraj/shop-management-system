@php
    use Illuminate\Support\Number;
@endphp

<div class="overflow-hidden border border-white/20 rounded">
    <table class="min-w-full divide-y divide-white/25">
        <thead class="bg-gray-800">
            <tr>
                <th scope="col" class="px-2 py-2 text-xs font-semibold uppercase text-left text-gray-300">
                    #
                </th>
                <th scope="col" class="px-2 py-2 text-xs font-semibold uppercase text-left text-gray-300">
                    Available
                </th>
                <th scope="col" class="px-2 py-2 text-xs font-semibold uppercase text-left text-gray-300">
                    Taken
                </th>
                <th scope="col" class="px-2 py-2 text-xs font-semibold uppercase text-left text-gray-300">
                    Unit COGS
                </th>
                <th scope="col" class="px-2 py-2 text-xs font-semibold uppercase text-left text-gray-300">
                    Total COGS
                </th>
                <th scope="col" class="px-2 py-2 text-xs font-semibold uppercase text-left text-gray-300">
                    Total Profit
                </th>
            </tr>
        </thead>

        <tbody class="divide-y divide-white/25">
            @foreach ($items as $key => $item)
                <tr class="">
                    <td class="whitespace-nowrap text-left px-2 py-1 text-xs">
                        {{ $key + 1 }} -
                    </td>
                    <td class="whitespace-nowrap px-2 py-1 text-sm">
                        {{ $item['available'] }} {{ $item['measurement'] }}
                    </td>
                    <td class="whitespace-nowrap px-2 py-1 text-sm">
                        {{ $item['taken'] }} {{ $item['measurement'] }}
                    </td>
                    <td class="whitespace-nowrap px-2 py-1 text-sm font-medium">
                        {{ Number::currency( $item['unit_cogs']) }}
                    </td>
                    <td class="whitespace-nowrap px-2 py-1 text-sm font-medium">
                        {{ Number::currency( $item['total_cogs']) }}
                    </td>
                    <td class="whitespace-nowrap px-2 py-1 text-sm font-medium">
                        {{ Number::currency( $item['profit']) }}
                    </td>
                </tr>
            @endforeach
        </tbody>

        <tfoot class="border-t border-white/25 bg-gray-800">
            <tr>
                <td colspan="4" class="px-2 py-2 uppercase text-right text-sm font-semibold">
                    Total:
                </td>
                <td class="px-2 py-2 text-sm font-bold">
                    {{ Number::currency($total_cogs) }}
                </td>
                <td class="px-2 py-2 text-sm font-bold">
                  {{ Number::currency($total_profit) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
