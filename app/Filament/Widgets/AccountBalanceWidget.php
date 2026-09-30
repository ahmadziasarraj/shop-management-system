<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Services\Accounting\AccountingBalanceWidgetService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class AccountBalanceWidget extends StatsOverviewWidget
{
    // protected string $view = 'filament.widgets.account-balance-widget';

    protected int | string | array $columnSpan = 'full';

    public Collection $accountData;

    protected function getStats(): array
    {

        $accounts = $this->accountData;

        return $accounts
            ->map(function (array $data) {
                return Stat::make(
                    $data['code'] . ' — ' . $data['name'],
                    Number::currency($data['balance'], 'USD')
                )
                    ->description(
                        new HtmlString(
                            $this->renderChildren($data['leaves'])
                        )
                    )
                    ->color(
                        $data['balance'] < 0
                            ? 'danger'
                            : 'success'
                    );
            })
            ->all();
    }

    private function renderChildren(
        Collection $children
    ): string {
        if (empty($children)) {
            return 'No transactions';
        }

        return $children
            ->map(function (array $child) {
                return sprintf(
                    '<div class="flex justify-between gap-4">
                        <span>%s — %s</span>
                        <span class="font-medium">%s</span>
                    </div>',
                    e($child['code']),
                    e($child['name']),
                    Number::currency($child['balance'], 'USD')
                );
            })
            ->implode('');
    }

    public function mount(
        AccountingBalanceWidgetService $balanceService
    ): void {
        $this->accountData = $balanceService
            ->getDashboardData();
    }
}
