<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_ai_budgets')) {
            return;
        }

        Schema::create('supplier_ai_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('openai');
            $table->decimal('monthly_limit', 10, 2)->default(100.00);
            $table->decimal('daily_limit', 10, 2)->nullable();
            $table->decimal('alert_threshold_pct', 5, 2)->default(80.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('block_on_exceed')->default(false);
            $table->text('notify_emails')->nullable();
            $table->timestamps();

            $table->unique('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_ai_budgets');
    }
};
