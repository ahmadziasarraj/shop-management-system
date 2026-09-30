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
        Schema::create('parties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name')->unique();
            $table->string('type');
            $table->decimal('limit', 20, 10)->nullable();
            $table->decimal('opening_receivable', 20, 10)->default(0);
            $table->decimal('opening_payable', 20, 10)->default(0);
            $table->decimal('opening_balance', 20, 10)->default(0);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
