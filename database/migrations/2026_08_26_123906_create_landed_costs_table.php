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
        Schema::create('landed_costs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('purchase_invoice_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUlid('party_id')
                ->constrained('parties')
                ->restrictOnDelete();
            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('hawala_office_id')
                ->nullable()
                ->constrained('parties')
                ->nullOnDelete();

            $table->decimal('exchange_rate', 20, 10);
            $table->decimal('paid_amount', 20, 10)->default(0);
            $table->decimal('amount', 20, 10)->default(0);
            $table->decimal('base_paid_amount', 20, 10)->default(0);
            $table->decimal('base_amount', 20, 10)->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('description');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('status');

            $table->timestamps();

            $table->index([
                'status',
            ]);
        });
        Schema::create('landed_cost_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('landed_cost_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUlid('purchase_invoice_line_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('allocation_basis');
            $table->decimal('basis_value', 20, 10);
            $table->decimal('allocated_amount', 20, 10);
            $table->decimal('base_allocated_amount', 20, 10);

            $table->decimal('quantity_purchased', 20, 6)->default(0);
            $table->decimal('quantity_affected', 20, 6)->default(0);
            $table->decimal('inventory_amount', 20, 6)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landed_cost_allocations');
        Schema::dropIfExists('landed_costs');
    }
};
