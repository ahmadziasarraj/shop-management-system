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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('party_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignUlid('hawala_office_id')
                ->nullable()
                ->constrained('parties')
                ->nullOnDelete();

            $table->string('original_invoice_number')->unique();
            $table->string('system_invoice_number')->unique();
            $table->unsignedBigInteger('invoice_sequence');

            $table->date('date');
            $table->decimal('exchange_rate', 20, 10);
            $table->decimal('subtotal', 20, 10);
            $table->decimal('discount_amount', 20, 10)->default(0);
            $table->decimal('total_amount', 20, 10);
            $table->decimal('paid_amount', 20, 10);
            $table->decimal('base_paid_amount', 20, 10);
            $table->decimal('base_subtotal', 20, 10);
            $table->decimal('base_discount_amount', 20, 10)->default(0);
            $table->decimal('base_total_amount', 20, 10);

            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('status');

            $table->timestamps();
        });
        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('purchase_invoice_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignUlid('currency_id')
                ->constrained()
                ->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 10);

            $table->decimal('quantity', 20, 6);
            $table->decimal('received_quantity', 20, 6)->default(0);
            $table->decimal('unit_cost', 20, 10);
            $table->decimal('total_cost', 20, 10);
            $table->decimal('base_unit_cost', 20, 10);
            $table->decimal('base_total_cost', 20, 10);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
    }
};
