<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data-fix: sync the `active` column (used by the TicketSlaPolicy model)
 * with `is_active` (written by HelpdeskTicketSlaPolicySeeder and the
 * 2026_04_19_000002 align migration).
 *
 * The canonical column is `active` because that is what the model reads
 * (scopeActive, casts, fillable).  `is_active` is kept in place to avoid
 * destructive DDL; we simply copy its value over to `active` when both
 * columns exist and a row's values differ.
 *
 * down() is intentionally a no-op: this is a data-synchronisation fix;
 * rolling it back would produce no benefit and risks reintroducing the
 * inconsistency.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (
            ! $schema->hasTable('helpdesk_ticket_sla_policies') ||
            ! $schema->hasColumn('helpdesk_ticket_sla_policies', 'active') ||
            ! $schema->hasColumn('helpdesk_ticket_sla_policies', 'is_active')
        ) {
            return;
        }

        // Copy is_active → active where they differ (handles both true and false)
        DB::connection($this->connection)
            ->statement('UPDATE `helpdesk_ticket_sla_policies` SET `active` = `is_active` WHERE `active` != `is_active`');
    }

    public function down(): void
    {
        // No-op — data synchronisation cannot be meaningfully reversed without
        // knowing the pre-migration state of every row.
    }
};
