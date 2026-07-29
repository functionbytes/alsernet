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
        Schema::create('supplier_automation_health_checks', function (Blueprint $table) {
            $table->id();
            $table->enum('check_type', ['server', 'workflow', 'webhook', 'credential'])->index();
            $table->string('target_id', 100)->nullable()->index();
            $table->enum('status', ['healthy', 'degraded', 'unhealthy', 'unknown'])->default('unknown')->index();
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_at');

            $table->index(['check_type', 'target_id']);
            $table->index(['status', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_health_checks');
    }
};
