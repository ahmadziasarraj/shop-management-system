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
        Schema::create('accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->string('code', 20);
            $table->string('name');

            $table->string('account_type', 30);
            $table->string('normal_balance', 10);

            $table->boolean('is_group')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['code']);

            $table->index(['parent_id']);
        });

        Schema::create('system_account_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('key');
            $table->foreignUlid('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([ 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_account_settings');
        Schema::dropIfExists('accounts');
    }
};
