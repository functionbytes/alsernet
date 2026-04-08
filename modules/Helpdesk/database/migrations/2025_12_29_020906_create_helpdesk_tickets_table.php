<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('sla_policy_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->string('subject');
            $table->longText('description')->nullable();
            $table->string('priority')->default('normal');
            $table->string('source')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('sla_first_response_due_at')->nullable();
            $table->timestamp('sla_next_response_due_at')->nullable();
            $table->timestamp('sla_resolution_due_at')->nullable();
            $table->boolean('sla_first_response_breached')->default(false);
            $table->boolean('sla_next_response_breached')->default(false);
            $table->boolean('sla_resolution_breached')->default(false);
            $table->timestamp('sla_paused_at')->nullable();
            $table->integer('sla_paused_duration_minutes')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('helpdesk_customers')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('helpdesk_ticket_categories')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('helpdesk_ticket_statuses')->onDelete('set null');
            $table->foreign('sla_policy_id')->references('id')->on('helpdesk_ticket_sla_policies')->onDelete('set null');
            $table->foreign('group_id')->references('id')->on('helpdesk_groups')->onDelete('set null');

            $table->index('ticket_number');
            $table->index('customer_id');
            $table->index('status_id');
            $table->index('assignee_id');
            $table->index('is_archived');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_tickets');
    }
};
