<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AccountingBalanceWidgetService
{
    /**
     * Get the financial dashboard data.
     */
    public function getDashboardData(): Collection
    {
        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $topLevelAccounts = $accounts
            ->whereNull('parent_id')
            ->values();

        /*
         * Find all leaf accounts and map them to
         * their top-level account.
         */
        $tree = $accounts->groupBy('parent_id');

        $leafAccounts = collect();

        foreach ($topLevelAccounts as $topLevel) {
            $this->collectLeafAccounts(
                $topLevel,
                $tree,
                $leafAccounts
            );
        }

        /*
         * One database query for all journal entries.
         */
        $balances = $this->getLeafBalances(
            $leafAccounts
        );

        /*
         * Build the final structure for the widgets.
         */
        return $topLevelAccounts->map(
            function (Account $topLevel) use (
                $tree,
                $leafAccounts,
                $balances
            ) {
                $leaves = $leafAccounts
                    ->where('top_level_id', $topLevel->id)
                    ->map(function (Account $account) use ($balances) {
                        $balanceData = $balances->get($account->id);

                        $debit = (float) (
                            $balanceData->total_debit ?? 0
                        );

                        $credit = (float) (
                            $balanceData->total_credit ?? 0
                        );

                        $balance = $this->calculateBalance(
                            $account,
                            $debit,
                            $credit
                        );

                        return [
                            'id' => $account->id,
                            'code' => $account->code,
                            'name' => $account->name,
                            'debit' => $debit,
                            'credit' => $credit,
                            'balance' => $balance,
                        ];
                    })
                    ->values();

                return [
                    'id' => $topLevel->id,
                    'code' => $topLevel->code,
                    'name' => $topLevel->name,
                    'account_type' => $topLevel->account_type,
                    'normal_balance' => $topLevel->normal_balance,
                    'balance' => $leaves->sum('balance'),
                    'leaves' => $leaves,
                ];
            }
        );
    }

    /**
     * Recursively find leaf accounts.
     */
    private function collectLeafAccounts(
        Account $account,
        Collection $tree,
        Collection &$leafAccounts,
        ?string $topLevelId = null
    ): void {
        $topLevelId ??= $account->id;

        $children = $tree->get($account->id, collect());

        if ($children->isEmpty()) {
            $account->top_level_id = $topLevelId;

            $leafAccounts->push($account);

            return;
        }

        foreach ($children as $child) {
            $this->collectLeafAccounts(
                $child,
                $tree,
                $leafAccounts,
                $topLevelId
            );
        }
    }

    /**
     * Aggregate all journal entries belonging to leaf accounts.
     */
    private function getLeafBalances(
        Collection $leafAccounts
    ): Collection {
        if ($leafAccounts->isEmpty()) {
            return collect();
        }

        return DB::table('journal_entries')
            ->join(
                'journals',
                'journals.id',
                '=',
                'journal_entries.journal_id'
            )
            ->whereIn(
                'journal_entries.account_id',
                $leafAccounts->pluck('id')
            )
            ->where(
                'journals.status',
                JournalStatus::Posted
            )
            ->select(
                'journal_entries.account_id',
                DB::raw(
                    'SUM(journal_entries.base_debit) as total_debit'
                ),
                DB::raw(
                    'SUM(journal_entries.base_credit) as total_credit'
                )
            )
            ->groupBy('journal_entries.account_id')
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Calculate balance according to the account's
     * normal balance.
     */
    private function calculateBalance(
        Account $account,
        float $debit,
        float $credit
    ): float {
        return match ($account->normal_balance) {
            NormalBalance::Debit => $debit - $credit,
            NormalBalance::Credit => $credit - $debit,
        };
    }
}
