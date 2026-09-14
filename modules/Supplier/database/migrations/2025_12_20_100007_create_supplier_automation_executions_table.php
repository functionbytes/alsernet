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
        Schema::create('supplier_automation_executions', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('ULID unique identifier');
            $table->foreignId('workflow_id')->constrained('supplier_automation_workflows')->cascadeOnDelete();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('supplier_sources')->nullOnDelete();
            $table->string('external_execution_id', 100)->nullable()->comment('Execution ID in orchestrator');
            $table->enum('status', ['pending', 'queued', 'running', 'completed', 'failed', 'timeout', 'cancelled'])->default('pending');
            $table->enum('trigger_type', ['manual', 'scheduled', 'webhook', 'event', 'dependent'])->default('manual');
            $table->json('input_payload')->nullable()->comment('Input data sent to workflow');
            $table->json('output_data')->nullable()->comment('Output data from workflow');
            $table->json('execution_context')->nullable()->comment('Execution context and variables');
            $table->json('error_details')->nullable()->comment('Error information if failed');
            $table->integer('duration_ms')->nullable()->comment('Execution duration in milliseconds');
            $table->integer('retry_count')->default(0);
            $table->integer('items_processed')->nullable()->comment('Number of items processed');
            $table->integer('items_succeeded')->nullable();
            $table->integer('items_failed')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('workflow_id');
            $table->index('status');
            $table->index('trigger_type');
            $table->index(['supplier_id', 'source_id']);
            $table->index('external_execution_id');
            $table->index('started_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_executions');
    }
};
