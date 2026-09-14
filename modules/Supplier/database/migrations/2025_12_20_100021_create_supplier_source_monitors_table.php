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
        Schema::create('supplier_source_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');

            // Estado actual
            $table->string('status', 30)->default('unknown'); // 'healthy', 'degraded', 'unhealthy', 'unreachable', 'unknown'
            $table->text('status_message')->nullable();
            $table->integer('status_code')->nullable();

            // Métricas de salud
            $table->decimal('uptime_percentage', 5, 2)->default(100.00);
            $table->integer('avg_response_time_ms')->nullable();
            $table->timestamp('last_successful_check_at')->nullable();
            $table->timestamp('last_failed_check_at')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->integer('consecutive_successes')->default(0);

            // Detección de cambios
            $table->string('structure_hash', 64)->nullable();
            $table->timestamp('structure_changed_at')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('content_changed_at')->nullable();

            // Configuración de monitoreo
            $table->integer('check_interval_minutes')->default(15);
            $table->string('health_check_url', 2048)->nullable();
            $table->string('expected_content_selector', 255)->nullable();

            // Alertas
            $table->boolean('alert_on_failure')->default(true);
            $table->boolean('alert_on_structure_change')->default(true);
            $table->timestamp('alert_sent_at')->nullable();
            $table->timestamp('snooze_alerts_until')->nullable();

            $table->timestamps();

            // Índices
            $table->index('source_id', 'idx_source_monitors_source');
            $table->index('status', 'idx_source_monitors_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_monitors');
    }
};
