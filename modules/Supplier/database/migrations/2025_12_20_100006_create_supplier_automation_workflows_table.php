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
        Schema::create('supplier_automation_workflows', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique()->comment('ULID unique identifier');
            $table->string('name', 100)->comment('Workflow name');
            $table->text('description')->nullable();
            $table->enum('workflow_type', ['extraction', 'scraping', 'ftp_sync', 'content_generation', 'validation', 'publication', 'monitoring']);
            $table->string('external_workflow_id', 100)->nullable()->comment('ID in orchestrator (n8n, Make, etc)');
            $table->string('webhook_url', 500)->nullable()->comment('Webhook URL to trigger workflow');
            $table->string('callback_url', 500)->nullable()->comment('Callback URL for workflow response');
            $table->json('workflow_config')->nullable()->comment('Workflow configuration');
            $table->json('default_variables')->nullable()->comment('Default variables for execution');
            $table->integer('timeout_seconds')->default(300)->comment('Execution timeout');
            $table->integer('max_retries')->default(3)->comment('Max retry attempts');
            $table->integer('priority')->default(5)->comment('Execution priority (1-10)');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_executed_at')->nullable();
            $table->integer('total_executions')->default(0);
            $table->integer('successful_executions')->default(0);
            $table->integer('failed_executions')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('workflow_type');
            $table->index('is_active');
            $table->index('external_workflow_id');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_workflows');
    }
};
