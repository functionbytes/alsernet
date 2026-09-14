<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Drop the redundant non-unique index on session_token.
     *
     * The ->unique() constraint already creates an index usable for lookups.
     * The additional ->index('session_token') is fully redundant and wastes
     * space while slowing down INSERT/UPDATE operations.
     */
    public function up(): void
    {
        $indexName = 'helpdesk_widget_sessions_session_token_index';

        $exists = collect(
            DB::connection($this->connection)
                ->select('SHOW INDEX FROM helpdesk_widget_sessions WHERE Key_name = ?', [$indexName])
        )->isNotEmpty();

        if (! $exists) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_widget_sessions', function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    /**
     * Restore the non-unique index if it does not already exist.
     */
    public function down(): void
    {
        $indexName = 'helpdesk_widget_sessions_session_token_index';

        $exists = collect(
            DB::connection($this->connection)
                ->select('SHOW INDEX FROM helpdesk_widget_sessions WHERE Key_name = ?', [$indexName])
        )->isNotEmpty();

        if ($exists) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_widget_sessions', function (Blueprint $table) {
            $table->index('session_token');
        });
    }
};
