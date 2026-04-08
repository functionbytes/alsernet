<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_recurring_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom']);
            $table->string('cron_expression')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('tickets_created')->default(0);
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')->on('helpdesk_ticket_categories')
                ->onDelete('set null');

            $table->foreign('priority_id')
                ->references('id')->on('helpdesk_priorities')
                ->onDelete('set null');

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_recurring_tickets');
    }
};
