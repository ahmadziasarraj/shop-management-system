<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_ledger_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('party_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference_type');
            $table->ulid('reference_id');

            $table->string('type');
            $table->decimal('debit_amount', 20, 6)->default(0);
            $table->decimal('credit_amount', 20, 6)->default(0);
            $table->decimal('base_debit_amount', 20, 6)->default(0);
            $table->decimal('base_credit_amount', 20, 6)->default(0);
            $table->decimal('exchange_rate', 20, 10)->default(0);

            $table->string('description');
            $table->date('transaction_date');

            $table->timestamps();

            $table->index([
                'party_id',
                'currency_id',
                'transaction_date',
            ]);

            $table->index([
                'reference_type',
                'reference_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_ledger_entries');
    }
};
