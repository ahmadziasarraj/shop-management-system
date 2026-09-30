<?php

namespace App\Actions\Accounting;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

final class CreateDefaultChartOfAccountsAction
{
    public function execute(): void
    {

        DB::transaction(function () {

            $accounts = [
                [
                    'code' => '1000',
                    'name' => 'Assets',
                    'type' => AccountType::Asset,
                    'balance' => NormalBalance::Debit,
                    'group' => true,
                    'children' => [
                        [
                            'code' => '1100',
                            'name' => 'Current Assets',
                            'type' => AccountType::Asset,
                            'balance' => NormalBalance::Debit,
                            'group' => true,
                            'children' => [
                                [
                                    'code' => '1110',
                                    'name' => 'Cash',
                                    'type' => AccountType::Asset,
                                    'balance' => NormalBalance::Debit,
                                ],
                                [
                                    'code' => '1120',
                                    'name' => 'Hawala Office',
                                    'type' => AccountType::Asset,
                                    'balance' => NormalBalance::Debit,
                                ],
                                [
                                    'code' => '1130',
                                    'name' => 'Accounts Receivable',
                                    'type' => AccountType::Asset,
                                    'balance' => NormalBalance::Debit,
                                ],
                                [
                                    'code' => '1140',
                                    'name' => 'Inventory',
                                    'type' => AccountType::Asset,
                                    'balance' => NormalBalance::Debit,
                                ],
                                [
                                    'code' => '1150',
                                    'name' => 'Bank',
                                    'type' => AccountType::Asset,
                                    'balance' => NormalBalance::Debit,
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'code' => '2000',
                    'name' => 'Liabilities',
                    'type' => AccountType::Liability,
                    'balance' => NormalBalance::Credit,
                    'group' => true,
                    'children' => [
                        [
                            'code' => '2100',
                            'name' => 'Current Liabilities',
                            'type' => AccountType::Liability,
                            'balance' => NormalBalance::Credit,
                            'group' => true,
                            'children' => [
                                [
                                    'code' => '2110',
                                    'name' => 'Accounts Payable',
                                    'type' => AccountType::Liability,
                                    'balance' => NormalBalance::Credit,
                                ],
                            ],
                        ],
                    ],
                ],

                [
                    'code' => '3000',
                    'name' => 'Equity',
                    'type' => AccountType::Equity,
                    'balance' => NormalBalance::Credit,
                    'group' => true,
                    'children' => [
                        [
                            'code' => '3100',
                            'name' => 'Owner Capital',
                            'type' => AccountType::Equity,
                            'balance' => NormalBalance::Credit,
                        ],
                        [
                            'code' => '3200',
                            'name' => 'Retained Earnings',
                            'type' => AccountType::Equity,
                            'balance' => NormalBalance::Credit,
                        ],
                        [
                            'code' => '3300',
                            'name' => 'Opening Balance Equity',
                            'type' => AccountType::Equity,
                            'balance' => NormalBalance::Credit,
                        ],
                    ],
                ],

                [
                    'code' => '4000',
                    'name' => 'Revenue',
                    'type' => AccountType::Revenue,
                    'balance' => NormalBalance::Credit,
                    'group' => true,
                    'children' => [
                        [
                            'code' => '4100',
                            'name' => 'Sales Revenue',
                            'type' => AccountType::Revenue,
                            'balance' => NormalBalance::Credit,
                        ],
                        [
                            'code' => '4200',
                            'name' => 'Sales Returns',
                            'type' => AccountType::Revenue,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '4300',
                            'name' => 'Other Revenue',
                            'type' => AccountType::Revenue,
                            'balance' => NormalBalance::Credit,
                        ],
                        [
                            'code' => '4400',
                            'name' => 'FX Gain',
                            'type' => AccountType::Revenue,
                            'balance' => NormalBalance::Credit,
                        ],
                    ],
                ],

                [
                    'code' => '5000',
                    'name' => 'Cost of Goods Sold',
                    'type' => AccountType::Cogs,
                    'balance' => NormalBalance::Debit,
                    'group' => true,
                    'children' => [
                        [
                            'code' => '5100',
                            'name' => 'Cost of Goods Sold',
                            'type' => AccountType::Cogs,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '5200',
                            'name' => 'Purchase Returns',
                            'type' => AccountType::Cogs,
                            'balance' => NormalBalance::Credit,
                        ],
                    ],
                ],

                [
                    'code' => '6000',
                    'name' => 'Operating Expenses',
                    'type' => AccountType::Expense,
                    'balance' => NormalBalance::Debit,
                    'group' => true,
                    'children' => [
                        [
                            'code' => '6100',
                            'name' => 'Rent',
                            'type' => AccountType::Expense,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '6200',
                            'name' => 'Salaries',
                            'type' => AccountType::Expense,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '6300',
                            'name' => 'Electricity',
                            'type' => AccountType::Expense,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '6400',
                            'name' => 'Internet',
                            'type' => AccountType::Expense,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '6700',
                            'name' => 'Other Expenses',
                            'type' => AccountType::Expense,
                            'balance' => NormalBalance::Debit,
                        ],
                        [
                            'code' => '6800',
                            'name' => 'FX Loss',
                            'type' => AccountType::Expense,
                            'balance' => NormalBalance::Debit,
                        ],
                    ],
                ],
            ];

            foreach ($accounts as $account) {
                $this->createAccount($account);
            }
        });
    }

    private function createAccount(
        array $data,
        ?string $parentId = null
    ): Account {

        $account = Account::create([
            'parent_id' => $parentId,

            'code' => $data['code'],
            'name' => $data['name'],

            'account_type' =>
            $data['type']->value,

            'normal_balance' =>
            $data['balance']->value,

            'is_group' =>
            $data['group'] ?? false,

            'is_system' => true,
            'is_active' => true,
        ]);

        foreach (
            $data['children'] ?? []
            as $child
        ) {
            $this->createAccount(
                $child,
                $account->id
            );
        }

        return $account;
    }
}
