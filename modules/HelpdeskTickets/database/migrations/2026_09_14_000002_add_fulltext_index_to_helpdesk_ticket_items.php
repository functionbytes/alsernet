<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    private const INDEX = 'helpdesk_ticket_items_body_fulltext';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ticket_items')
            || ! $schema->hasColumn('helpdesk_ticket_items', 'body')
            || ! $schema->hasColumn('helpdesk_ticket_items', 'html_body')
            || ! in_array(DB::connection($this->connection)->getDriverName(), ['mysql', 'mariadb'], true)
            || $this->indexExists()) {
            return;
        }

        $schema->table('helpdesk_ticket_items', function (Blueprint $table): void {
            $table->fullText(['body', 'html_body'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_items') && $this->indexExists()) {
            Schema::connection($this->connection)->table('helpdesk_ticket_items', function (Blueprint $table): void {
                $table->dropFullText(self::INDEX);
            });
        }
    }

    private function indexExists(): bool
    {
        return DB::connection($this->connection)
            ->select('SHOW INDEX FROM `helpdesk_ticket_items` WHERE Key_name = ?', [self::INDEX]) !== [];
    }
};
