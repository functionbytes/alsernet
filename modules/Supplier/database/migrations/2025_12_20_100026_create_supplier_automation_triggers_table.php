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
        Schema::create('supplier_automation_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();

            $table->string('name', 100);
            $table->text('description')->nullable();

            // Trigger type
            $table->string('trigger_type', 50);
            // 'schedule', 'webhook', 'file_upload', 'api_call', 'source_change', 'manual', 'dependent'

            // Configuration
            $table->json('trigger_config')->nullable();
            // Schedule config (cron expression), webhook config, dependency config, etc.

            // Workflow to execute
            $table->foreignId('workflow_id')->nullable()->constrained('supplier_automation_workflows')->onDelete('cascade');

            // Filtering and context
            $table->json('source_filter')->nullable();
            // Filter by supplier, source type, etc.
            $table->json('execution_context')->nullable();
            // Variables to pass to the workflow

            // Conditions
            $table->json('trigger_conditions')->nullable();
            // Additional conditions to check before triggering

            // State
            $table->boolean('is_enabled')->default(true);
            $table->timestampTz('last_triggered_at')->nullable();
            $table->integer('total_triggers')->default(0);

            $table->timestampsTz();

            // Indexes
            $table->index('trigger_type');
            $table->index('workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_triggers');
    }
};
