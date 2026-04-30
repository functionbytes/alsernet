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
        Schema::create('chat_automation_scheduled', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type')->index('scheduled_automations_trigger_type_index');
            $table->json('conditions');
            $table->json('actions');
            $table->integer('schedule_minutes')->nullable();
            $table->string('schedule_cron')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['account_id', 'active'], 'scheduled_automations_account_id_active_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_automation_scheduled');
    }
};
