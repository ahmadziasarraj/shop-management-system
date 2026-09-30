<?php

use App\Actions\Accounting\ConfigureSystemAccountsAction;
use App\Actions\Accounting\CreateDefaultChartOfAccountsAction;
use App\Actions\Resources\UnitMeasurementActions;
use App\Enums\SystemAccount;
use App\Models\Account;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/add-coa', function (CreateDefaultChartOfAccountsAction $action) {
    $action->execute();
});

Route::get('/add-sys-ccounts', function () {
    $accountsToAdd = [];
    Account::all()->where('is_group', false)->map(function ($account) use (&$accountsToAdd) {
        $value = str($account['name'])
            ->snake()
            ->lower()
            ->toString();

        if (SystemAccount::tryFrom($value)) {
            array_push($accountsToAdd, [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'system_account' => SystemAccount::tryFrom($value),
            ]);
        }elseif ($account->name == 'Cost of Goods Sold') {
            array_push($accountsToAdd, [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'system_account' => SystemAccount::Cogs,
            ]);
        }
    })->toArray();

    $action = new ConfigureSystemAccountsAction;
    foreach ($accountsToAdd as $value) {
        $action->execute($value['system_account'], $value['id']);
    }
});

Route::get('/add-units', function (UnitMeasurementActions $action) {
    $action->insertDefaultUnits();
});
