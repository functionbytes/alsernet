<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align `helpdesk_tickets` with the schema the Ticket model/code expect.
 *
 * Historical context: the DB was initialized with a simplified, legacy schema
 * (title, status, category, assigned_to, user_id…) that predates the modern
 * model. The base `create_helpdesk_tickets_table` migration never replaced it.
 * This migration drops-and-recreates only if the table is empty, otherwise
 * adds missing columns non-destructively.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_tickets')) {
            $this->createTable();

            return;
        }

        // Always add-missing (non-destructive). FK constraints prevent drop/recreate.
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            $this->addMissingColumns($table);
        });
    }

    public function down(): void
    {
        // Irreversible beyond best-effort.
    }

    private function createTable(): void
    {
        Schema::connection($this->connection)->create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
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
            $table->integer('rating')->nullable();
            $table->text('rating_comment')->nullable();
            $table->timestamp('rated_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->integer('escalation_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_number');
            $table->index('customer_id');
            $table->index('status_id');
            $table->index('assignee_id');
            $table->index('is_archived');
            $table->index('created_at');
            $table->index(['sla_resolution_breached', 'closed_at']);
        });
    }

    private function addMissingColumns(Blueprint $table): void
    {
        $schema = Schema::connection($this->connection);

        $additions = [
            ['customer_id', fn () => $table->unsignedBigInteger('customer_id')->nullable()->after('id')],
            ['category_id', fn () => $table->unsignedBigInteger('category_id')->nullable()],
            ['status_id', fn () => $table->unsignedBigInteger('status_id')->nullable()],
            ['sla_policy_id', fn () => $table->unsignedBigInteger('sla_policy_id')->nullable()],
            ['group_id', fn () => $table->unsignedBigInteger('group_id')->nullable()],
            ['assignee_id', fn () => $table->unsignedBigInteger('assignee_id')->nullable()],
            ['subject', fn () => $table->string('subject')->nullable()],
            ['source', fn () => $table->string('source')->nullable()],
            ['custom_fields', fn () => $table->json('custom_fields')->nullable()],
            ['tags', fn () => $table->json('tags')->nullable()],
            ['assigned_at', fn () => $table->timestamp('assigned_at')->nullable()],
            ['first_response_at', fn () => $table->timestamp('first_response_at')->nullable()],
            ['last_message_at', fn () => $table->timestamp('last_message_at')->nullable()],
            ['sla_first_response_due_at', fn () => $table->timestamp('sla_first_response_due_at')->nullable()],
            ['sla_next_response_due_at', fn () => $table->timestamp('sla_next_response_due_at')->nullable()],
            ['sla_resolution_due_at', fn () => $table->timestamp('sla_resolution_due_at')->nullable()],
            ['sla_first_response_breached', fn () => $table->boolean('sla_first_response_breached')->default(false)],
            ['sla_next_response_breached', fn () => $table->boolean('sla_next_response_breached')->default(false)],
            ['sla_resolution_breached', fn () => $table->boolean('sla_resolution_breached')->default(false)],
            ['sla_paused_at', fn () => $table->timestamp('sla_paused_at')->nullable()],
            ['sla_paused_duration_minutes', fn () => $table->integer('sla_paused_duration_minutes')->default(0)],
            ['is_archived', fn () => $table->boolean('is_archived')->default(false)],
            ['rating', fn () => $table->integer('rating')->nullable()],
            ['rating_comment', fn () => $table->text('rating_comment')->nullable()],
            ['rated_at', fn () => $table->timestamp('rated_at')->nullable()],
            ['escalated_at', fn () => $table->timestamp('escalated_at')->nullable()],
            ['escalation_count', fn () => $table->integer('escalation_count')->default(0)],
            ['deleted_at', fn () => $table->softDeletes()],
        ];

        foreach ($additions as [$column, $adder]) {
            if (! $schema->hasColumn('helpdesk_tickets', $column)) {
                $adder();
            }
        }
    }
};
