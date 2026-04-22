<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align `helpdesk_ticket_views` with the TicketView model expectations.
 *
 * The legacy schema tracked "which user viewed which ticket" (ticket_id,
 * viewed_at). The model instead represents named filter presets (name,
 * filters, sort_by, is_default, order). Adds missing columns; legacy
 * tracking columns are kept for now to avoid data loss.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_views')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ticket_views', function (Blueprint $table) {
            $schema = Schema::connection($this->connection);

            if (! $schema->hasColumn('helpdesk_ticket_views', 'name')) {
                $table->string('name')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'description')) {
                $table->text('description')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'filters')) {
                $table->json('filters')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'sort_by')) {
                $table->string('sort_by')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'sort_direction')) {
                $table->string('sort_direction', 8)->default('desc');
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'is_shared')) {
                $table->boolean('is_shared')->default(false);
            }
            if (! $schema->hasColumn('helpdesk_ticket_views', 'order')) {
                $table->unsignedInteger('order')->default(0)->index();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive migration — no rollback.
    }
};
