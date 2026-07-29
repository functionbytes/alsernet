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
        Schema::create('supplier_automation_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('limitable_type', 100)->index();
            $table->unsignedBigInteger('limitable_id')->index();
            $table->enum('window_type', ['minute', 'hour', 'day'])->index();
            $table->integer('max_requests')->default(0);
            $table->integer('current_count')->default(0);
            $table->timestamp('window_start')->useCurrent();
            $table->timestamp('blocked_until')->nullable()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['limitable_type', 'limitable_id'], 'rate_limits_limitable_idx');
            $table->index(['window_type', 'window_start'], 'rate_limits_window_idx');
            $table->unique(['limitable_type', 'limitable_id', 'window_type'], 'rate_limit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_rate_limits');
    }
};
