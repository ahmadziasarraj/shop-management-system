<?php

namespace App\Enums;

enum SystemAccount: string
{
    case Cash = 'cash';
    case HawalaOffice = 'hawala_office';
    case AccountsReceivable = 'accounts_receivable';
    case Inventory = 'inventory';
    case Bank = 'bank';
    case AccountsPayable = 'accounts_payable';
    case OwnerCapital = 'owner_capital';
    case RetainedEarnings = 'retained_earnings';
    case SalesRevenue = 'sales_revenue';
    case SalesReturns = 'sales_returns';
    case Cogs = 'cogs';
    case OtherExpense = 'other_expenses';

}
