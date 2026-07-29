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
        Schema::create('supplier_automation_chain_executions', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('chain_id')->constrained('supplier_automation_chains')->onDelete('cascade');

            // Global state
            $table->string('status', 30)->default('pending');
            // 'pending', 'running', 'paused', 'waiting_approval', 'completed', 'failed', 'cancelled'

            // Progress
            $table->string('current_stage', 100)->nullable();
            $table->json('completed_stages')->nullable();
            $table->json('failed_stages')->nullable();

            // Accumulated context
            $table->json('execution_context')->nullable();
            $table->json('stage_results')->nullable();

            // Timing
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            // Approval (if required)
            $table->string('pending_approval_stage', 100)->nullable();
            $table->timestampTz('approval_requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestampTz('approved_at')->nullable();

            // Source
            $table->string('triggered_by', 50)->nullable();
            // 'manual', 'schedule', 'api', 'trigger'
            $table->foreignId('triggered_by_user')->nullable()->constrained('users')->onDelete('set null');

            $table->timestampsTz();

            // Indexes
            $table->index('chain_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_chain_executions');
    }
};
