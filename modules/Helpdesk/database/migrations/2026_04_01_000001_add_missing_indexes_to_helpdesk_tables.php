<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_comments', function (Blueprint $table): void {
            $table->index('user_id');
        });

        Schema::connection($this->connection)->table('helpdesk_ticket_notes', function (Blueprint $table): void {
            $table->index('user_id');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->index('user_id');
            $table->index('author_id');
        });

        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table): void {
            $table->index(['assignee_id', 'status_id'], 'helpdesk_tickets_assignee_status_index');
            $table->index('sla_resolution_due_at');
            $table->index(['customer_id', 'created_at'], 'helpdesk_tickets_customer_created_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_comments', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::connection($this->connection)->table('helpdesk_ticket_notes', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['author_id']);
        });

        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table): void {
            $table->dropIndex('helpdesk_tickets_assignee_status_index');
            $table->dropIndex(['sla_resolution_due_at']);
            $table->dropIndex('helpdesk_tickets_customer_created_index');
        });
    }
};
