<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FULLTEXT INDEXES for efficient text search:
     * - Message content search (currently uses LIKE %term%)
     * - Customer name/email search
     * - Canned response content search
     *
     * MariaDB/MySQL requirement: FULLTEXT only works on TEXT/VARCHAR columns.
     */
    public function up(): void
    {
        // Check database engine supports FULLTEXT
        $engine = DB::connection()->getDriverName();

        if (! in_array($engine, ['mysql', 'mariadb'])) {
            return;
        }

        $this->addFulltext('chat_conversation_messages', 'hd_messages_content_ft', '(content)');
        $this->addFulltext('chat_customers', 'hd_customers_search_ft', '(name, email)');
        $this->addFulltext('chat_canneds', 'hd_canneds_content_ft', '(short_code, content)');
        $this->addFulltext('chat_helpcenter_articles', 'hd_articles_search_ft', '(title, content)');
        $this->addFulltext('chat_conversations', 'hd_conv_subject_ft', '(subject)');
    }

    private function addFulltext(string $table, string $index, string $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columnList = array_map('trim', explode(',', trim($columns, '() ')));
        foreach ($columnList as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        $database = DB::getDatabaseName();
        $exists = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        )->c > 0;

        if ($exists) {
            return;
        }

        DB::statement("ALTER TABLE {$table} ADD FULLTEXT INDEX {$index} {$columns}");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $engine = DB::connection()->getDriverName();

        if (! in_array($engine, ['mysql', 'mariadb'])) {
            return;
        }

        DB::statement('ALTER TABLE chat_conversations DROP INDEX hd_conv_subject_ft');

        if (Schema::hasTable('chat_helpcenter_articles')) {
            DB::statement('ALTER TABLE chat_helpcenter_articles DROP INDEX hd_articles_search_ft');
        }

        DB::statement('ALTER TABLE chat_canneds DROP INDEX hd_canneds_content_ft');
        DB::statement('ALTER TABLE chat_customers DROP INDEX hd_customers_search_ft');
        DB::statement('ALTER TABLE chat_conversation_messages DROP INDEX hd_messages_content_ft');
    }
};
