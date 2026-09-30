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
        Schema::create('journals', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('journal_number');
            $table->unsignedBigInteger('journal_sequence');

            $table->date('journal_date');

            $table->string('reference_type')->nullable();
            $table->ulid('reference_id')->nullable();

            $table->text('description')->nullable();

            $table->string('status')->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['journal_number', 'journal_sequence']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('journal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUlid('account_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('debit', 20, 6)->default(0);
            $table->decimal('credit', 20, 6)->default(0);

            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('exchange_rate', 20, 10);

            $table->decimal('base_debit', 20, 6)->default(0);
            $table->decimal('base_credit', 20, 6)->default(0);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('journal_id');
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journals');
    }
};
