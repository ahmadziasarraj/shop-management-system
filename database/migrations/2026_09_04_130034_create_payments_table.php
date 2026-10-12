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
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('party_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference_type');
            $table->ulid('reference_id');
            $table->string('payment_number');
            $table->string('payment_method');
            $table->decimal('amount', 20, 10);
            $table->decimal('base_amount', 20, 10)->default(0);
            $table->decimal('exchange_rate', 20, 10)->default(0);
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index([
                'party_id',
                'payment_date',
            ]);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {

            $table->ulid('id')->primary();

            $table->string('document_type');
            $table->ulid('document_id');

            $table->decimal('amount', 20, 6);

            /*
            * Amount allocated against the invoice's
            * own currency.
            */
            $table->decimal('document_amount', 20, 6);
            $table->decimal('base_amount', 20, 6);
            $table->decimal('exchange_rate', 20, 10)->nullable();

            $table->timestamps();

            $table->index([
                'document_type',
                'document_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_allocations');
    }
};
