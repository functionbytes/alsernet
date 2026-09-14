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
        Schema::create('supplier_automation_dead_letter_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('execution_id')->index();
            $table->enum('failure_reason', ['max_retries', 'invalid_config', 'source_gone', 'timeout', 'manual'])->index();
            $table->json('error_details')->nullable();
            $table->json('original_payload')->nullable();
            $table->enum('requires_action', ['retry', 'fix_config', 'contact_supplier', 'skip', 'none'])->default('none')->index();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('execution_id')
                ->references('id')
                ->on('supplier_automation_executions')
                ->onDelete('cascade');

            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['failure_reason', 'created_at'], 'dlq_failure_created_idx');
            $table->index(['requires_action', 'resolved_at'], 'dlq_action_resolved_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_automation_dead_letter_queue');
    }
};
