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
        Schema::create('stock_items', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('stock_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('quantity', 20, 6)->default(0);
            $table->decimal('quantity_consumed', 20, 6)->default(0);
            $table->decimal('quantity_reserved', 20, 6)->default(0);
            $table->decimal('quantity_on_hand', 20, 6)->default(0);

            $table->decimal('purchased_amount', 20, 10);
            $table->decimal('unit_landed_cost', 20, 10)->default(0);
            $table->decimal('unit_total_cogs', 20, 10)->default(0);
            $table->decimal('total_inventory', 20, 10)->default(0);
            $table->decimal('current_inventory', 20, 10)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
