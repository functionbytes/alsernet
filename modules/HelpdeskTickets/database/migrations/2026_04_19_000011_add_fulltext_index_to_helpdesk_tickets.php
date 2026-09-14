<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * FULLTEXT index on ticket search columns for fast LIKE-free search.
     *
     * Only adds the index if the table and both columns exist, and if MariaDB
     * allows FULLTEXT on this engine. Gracefully skips otherwise so the
     * migration never blocks deployments.
     */
    public function up(): void
    {
        $connection = $this->connection;

        if (! Schema::connection($connection)->hasTable('helpdesk_tickets')) {
            return;
        }

        $columns = array_values(array_filter(
            ['title', 'description'],
            fn (string $col) => Schema::connection($connection)->hasColumn('helpdesk_tickets', $col)
        ));

        if (empty($columns)) {
            return;
        }

        $indexName = 'helpdesk_tickets_search_fulltext';

        $exists = collect(DB::connection($connection)
            ->select('SHOW INDEX FROM helpdesk_tickets WHERE Key_name = ?', [$indexName]))
            ->isNotEmpty();

        if ($exists) {
            return;
        }

        $columnList = implode(', ', $columns);

        try {
            DB::connection($connection)
                ->statement("ALTER TABLE helpdesk_tickets ADD FULLTEXT {$indexName} ({$columnList})");
        } catch (Throwable $e) {
            // FULLTEXT may not be supported by the storage engine (e.g. older MyISAM
            // without the feature, or tables with mismatched collation). Log and
            // skip so the migration never blocks; LIKE search still works.
            logger()->warning('FULLTEXT index skipped on helpdesk_tickets', [
                'error' => $e->getMessage(),
                'columns' => $columnList,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_tickets')) {
            return;
        }

        $indexName = 'helpdesk_tickets_search_fulltext';

        $exists = collect(DB::connection($this->connection)
            ->select('SHOW INDEX FROM helpdesk_tickets WHERE Key_name = ?', [$indexName]))
            ->isNotEmpty();

        if ($exists) {
            DB::connection($this->connection)
                ->statement("ALTER TABLE helpdesk_tickets DROP INDEX {$indexName}");
        }
    }
};
