<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:clear-shop-data')]
#[Description('Command description')]
class ClearShopData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('journal_entries')->delete();
        DB::table('journals')->delete();
        DB::table('landed_cost_allocations')->delete();
        DB::table('landed_costs')->delete();
        DB::table('party_ledger_entries')->delete();
        DB::table('payments')->delete();
        DB::table('purchase_invoice_lines')->delete();
        DB::table('purchase_invoices')->delete();
        DB::table('purchase_receipt_lines')->delete();
        DB::table('purchase_receipts')->delete();
        DB::table('stock_balances')->delete();
        DB::table('stock_items')->delete();
        DB::table('stock_movements')->delete();

        $this->info('Shop data cleared.');
    }
}
