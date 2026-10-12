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
        Schema::table('payments', function (Blueprint $table) {

            $table->foreignUlid('hawala_office_id')
                ->nullable()
                ->constrained('parties')
                ->nullOnDelete();
            $table->string('transaction_type')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('hawala_office_id');
            $table->dropColumn('transaction_type');
        });
    }
};
