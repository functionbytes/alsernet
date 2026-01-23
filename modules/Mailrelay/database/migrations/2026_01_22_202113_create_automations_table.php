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
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', ['event', 'condition', 'schedule'])->default('event');
            $table->json('trigger_config')->nullable(); // Configuration for the trigger
            $table->enum('action_type', ['send_email', 'add_tag', 'change_group', 'webhook'])->default('send_email');
            $table->json('action_config')->nullable(); // Configuration for the action
            $table->json('conditions')->nullable(); // Optional conditions to evaluate
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->integer('total_executions')->default(0);
            $table->integer('successful_executions')->default(0);
            $table->integer('failed_executions')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('trigger_type');
            $table->index('action_type');
            $table->index('last_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
