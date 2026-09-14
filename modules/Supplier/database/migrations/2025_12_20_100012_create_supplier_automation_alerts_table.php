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
        Schema::create('supplier_automation_alerts', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->enum('alert_type', [
                'server_unreachable',
                'workflow_disabled',
                'high_failure_rate',
                'rate_limit_exceeded',
                'credential_expiring',
                'credential_expired',
                'execution_timeout',
                'dead_letter_threshold',
                'queue_backlog',
                'scraping_blocked',
            ])->index();
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->index();
            $table->string('title', 255);
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('related_type', 100)->nullable()->index();
            $table->unsignedBigInteger('related_id')->nullable()->index();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('acknowledged_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['related_type', 'related_id'], 'alerts_related_idx');
            $table->index(['alert_type', 'severity'], 'alerts_type_severity_idx');
            $table->index(['severity', 'created_at'], 'alerts_severity_created_idx');
            $table->index(['resolved_at', 'acknowledged_at'], 'alerts_resolved_ack_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_alerts');
    }
};
