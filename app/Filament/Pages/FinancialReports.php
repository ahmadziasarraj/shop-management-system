<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountBalanceWidget;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;

class FinancialReports extends Page
{
    protected string $view = 'filament.pages.financial-reports';

    protected function getHeaderWidgets(): array
    {
        return [
            AccountBalanceWidget::class
        ];
    }
}
