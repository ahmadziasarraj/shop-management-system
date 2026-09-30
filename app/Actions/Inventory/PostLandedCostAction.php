<?php

namespace App\Actions\Inventory;

use App\Actions\Accounting\CreateJournalAction;
use App\Actions\Accounting\PostJournalAction;
use App\Enums\LandedCostStatus;
use App\Models\LandedCost;
use App\Services\Inventory\InventoryCostService;
use App\Services\Inventory\LandedCostPostingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PostLandedCostAction
{
    public function __construct(
        private readonly LandedCostPostingService $postingService,
        private readonly InventoryCostService $inventoryCost,
        private readonly CreateJournalAction $createJournal,
        private readonly PostJournalAction $postJournal,
    ) {}

    public function execute(
        LandedCost $landedCost,
    ): LandedCost {

        return DB::transaction(
            function () use ($landedCost) {

                $landedCost->refresh();

                if ($landedCost->status !== LandedCostStatus::Draft) {
                    throw new RuntimeException(
                        'Only draft landed costs can be posted.'
                    );
                }

                if ($landedCost->allocations()->count() === 0) {
                    throw new RuntimeException(
                        'Please allocate landed cost first.'
                    );
                }

                $this->postingService->post(
                    $landedCost
                );
                $landedCost->update([
                    'status' => LandedCostStatus::Posted
                ]);
                $landedCost->refresh();

                /*
                 * Apply cost to inventory cost layer.
                 */
                // $this->inventoryCost->applyLandedCost(
                //     $landedCost
                // );


                /*
                 * Create accounting journal.
                 *
                 * Dr Inventory
                 * Cr AP / Accrued Freight / Cash
                 *
                 * The exact credit account depends
                 * on payment/accrual state.
                 */

                // $journal = $this->createJournal->execute([
                //     'journalDate' => $landedCost->landed_cost_date,
                //     'referenceType' => LandedCost::class,
                //     'referenceId' => $landedCost->id,
                //     'description' => "Landed cost {$landedCost->reference_number}",
                // ]);

                // $this->postJournal->execute(
                //     $journal,
                //     $userId
                // );

                // $landedCost->update([
                //     'status' => 'posted'
                // ]);

                return $landedCost;
            }
        );
    }
}
