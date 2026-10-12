<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountBalanceWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use UnitEnum;

class FinancialReports extends Page
{
    protected string $view = 'filament.pages.financial-reports';
    protected static string|UnitEnum|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartPie;

    protected function getHeaderWidgets(): array
    {
        return [
            AccountBalanceWidget::class
        ];
    }
}
