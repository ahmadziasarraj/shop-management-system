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
        Schema::create('stocks', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name')->unique();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('stock_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->nullableUlidMorphs('reference');

            $table->string('type');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 10)->default(0);
            $table->decimal('total_cost', 20, 10)->default(0);

            $table->timestamps();

            $table->index([
                'stock_id',
                'product_variant_id',
            ]);
        });
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('stock_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('quantity_on_hand', 20, 6);
            $table->decimal('quantity_reserved', 20, 6);
            $table->decimal('total_cost', 20, 10)->default(0);
            $table->decimal('average_cost', 20, 10)->default(0);

            $table->timestamps();
            $table->index([
                'stock_id',
                'product_variant_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
    }
};
