<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `deleted_at` column to tables whose Eloquent models declare the
 * SoftDeletes trait but whose creation migrations never added the column.
 *
 * Without this column, every query against those models fails with
 * "Unknown column 'deleted_at'". All additions are guarded with hasTable() +
 * hasColumn() so the migration is idempotent and safely skips tables that do
 * not exist (e.g. legacy duplicate models pointing at tables that were never
 * created).
 *
 * Tables: helpdesk_ticket_messages, helpdesk_categories, helpdesk_statuses,
 *         helpdesk_priorities
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    /** @var list<string> */
    private array $tables = [
        'helpdesk_ticket_messages',
        'helpdesk_categories',
        'helpdesk_statuses',
        'helpdesk_priorities',
    ];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        foreach ($this->tables as $tableName) {
            if (! $schema->hasTable($tableName) || $schema->hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            $schema->table($tableName, function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        foreach ($this->tables as $tableName) {
            if (! $schema->hasTable($tableName) || ! $schema->hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            $schema->table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
