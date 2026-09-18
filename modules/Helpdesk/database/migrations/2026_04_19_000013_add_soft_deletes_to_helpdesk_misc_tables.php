<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Backfill `deleted_at` on tables whose models need SoftDeletes but whose
     * original migrations never declared the column.
     */
    private array $tables = [
        'helpdesk_ticket_messages',
        'helpdesk_groups',
        'helpdesk_ticket_statuses',
        'helpdesk_ticket_categories',
        'helpdesk_ticket_sla_policies',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            $hasTable = Schema::connection($this->connection)->hasTable($tableName);
            $hasColumn = $hasTable && Schema::connection($this->connection)->hasColumn($tableName, 'deleted_at');

            if ($hasTable && ! $hasColumn) {
                Schema::connection($this->connection)->table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            $hasTable = Schema::connection($this->connection)->hasTable($tableName);
            $hasColumn = $hasTable && Schema::connection($this->connection)->hasColumn($tableName, 'deleted_at');

            if ($hasTable && $hasColumn) {
                Schema::connection($this->connection)->table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
