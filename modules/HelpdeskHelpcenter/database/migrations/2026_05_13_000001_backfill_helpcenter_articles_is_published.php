<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_helpcenter_articles')) {
            return;
        }

        $hasColumns = Schema::connection($this->connection)->hasColumn('helpdesk_helpcenter_articles', 'draft')
            && Schema::connection($this->connection)->hasColumn('helpdesk_helpcenter_articles', 'is_published');

        if (! $hasColumns) {
            return;
        }

        DB::connection($this->connection)
            ->table('helpdesk_helpcenter_articles')
            ->update(['is_published' => DB::raw('draft = 0')]);

        DB::connection($this->connection)
            ->table('helpdesk_helpcenter_articles')
            ->where('is_published', true)
            ->whereNull('published_at')
            ->update(['published_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Irreversible data backfill — nothing to roll back.
    }
};
