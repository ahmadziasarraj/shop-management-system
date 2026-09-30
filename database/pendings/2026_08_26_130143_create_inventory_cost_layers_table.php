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
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('stock_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignUlid('purchase_invoice_line_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUlid('landed_cost_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('quantity', 20, 6); //Quantity belonging to this cost layer.
            $table->decimal('quantity_consumed', 20, 6)->default(0); //Quantity already consumed/sold.

            $table->decimal('unit_cost', 20, 10); //Original product cost per unit.
            $table->decimal('landed_cost_per_unit', 20, 10)->default(0); //Additional landed cost per unit.
            $table->decimal('total_unit_cost', 20, 10); //Final inventory cost per unit.
            $table->decimal('total_cost', 20, 10); //Total base-currency value.

            $table->timestamps();

            $table->index([
                'stock_id',
                'product_variant_id',
            ]);
        });

        Schema::create('inventory_cost_adjustments', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('stock_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignUlid('landed_cost_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUlid('landed_cost_allocation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('quantity_affected', 20, 6);
            $table->decimal('unit_adjustment', 20, 6);
            $table->decimal('amount', 20, 6);
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
        Schema::dropIfExists('inventory_cost_adjustments');
    }
};
