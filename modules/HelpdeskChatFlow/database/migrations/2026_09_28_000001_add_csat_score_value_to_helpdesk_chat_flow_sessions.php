<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    private const TABLE = 'helpdesk_chat_flow_sessions';

    private const INDEX = 'hcfs_flow_started_csat_index';

    /**
     * CSAT analytics filtered by `context->csat_score`, which MariaDB cannot
     * index on a JSON column. A VIRTUAL generated column exposes the score (no
     * extra storage, computed on read) so it can be indexed.
     *
     * The composite (chat_flow_id, started_at, csat_score_value) serves every
     * analytics builder by prefix (flow + date window) and covers the CSAT query
     * entirely, so it never touches the context blob.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        if (! $schema->hasColumn(self::TABLE, 'csat_score_value')) {
            // SQLite (tests) has no JSON_UNQUOTE; its json_extract already unquotes.
            $expression = $schema->getConnection()->getDriverName() === 'sqlite'
                ? "json_extract(context, '$.csat_score')"
                : "JSON_UNQUOTE(JSON_EXTRACT(`context`, '$.csat_score'))";

            $schema->table(self::TABLE, function (Blueprint $table) use ($expression) {
                $table->string('csat_score_value', 32)
                    ->nullable()
                    ->virtualAs($expression)
                    ->after('context');
            });
        }

        try {
            $schema->table(self::TABLE, fn (Blueprint $table) => $table->index(['chat_flow_id', 'started_at', 'csat_score_value'], self::INDEX));
        } catch (Throwable $e) {
            // Index already exists — safe to ignore (idempotent re-run).
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable(self::TABLE)) {
            return;
        }

        try {
            $schema->table(self::TABLE, fn (Blueprint $table) => $table->dropIndex(self::INDEX));
        } catch (Throwable $e) {
            // Index missing — safe to ignore.
        }

        if ($schema->hasColumn(self::TABLE, 'csat_score_value')) {
            $schema->table(self::TABLE, fn (Blueprint $table) => $table->dropColumn('csat_score_value'));
        }
    }
};
