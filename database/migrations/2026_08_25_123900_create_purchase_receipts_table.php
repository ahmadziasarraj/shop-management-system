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
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUlid('purchase_invoice_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('receipt_number')->nullable();
            $table->date('receipt_date');
            $table->string('status');

            $table->timestamps();
        });
        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignUlid('purchase_receipt_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('purchase_invoice_line_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignUlid('product_variant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 10);
            $table->decimal('total_cost', 20, 10);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('purchase_receipt_lines');
    }
};
