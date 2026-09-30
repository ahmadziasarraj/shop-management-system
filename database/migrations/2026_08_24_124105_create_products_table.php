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
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name')->unique();
            $table->string('sku')->unique();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('sku');
        });
        Schema::create('unit_measurements', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name')->unique();
            $table->string('symbol')->unique();
            $table->timestamps();

            $table->index('name');
            $table->index('symbol');
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('unit_measurement_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name')->unique();
            $table->string('sku')->unique();
            $table->string('model')->nullable();
            $table->string('company')->nullable();
            $table->decimal('cost_price', 14, 2)->nullable();
            $table->decimal('selling_price', 14, 2)->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('unit_measurements');
        Schema::dropIfExists('products');
    }
};
