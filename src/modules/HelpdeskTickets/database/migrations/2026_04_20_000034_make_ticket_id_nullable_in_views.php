<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $conn = DB::connection($this->connection);

        $fkExists = $conn->select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'helpdesk_ticket_views'
              AND COLUMN_NAME = 'ticket_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($fkExists as $fk) {
            $conn->statement("ALTER TABLE `helpdesk_ticket_views` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $conn->statement('ALTER TABLE `helpdesk_ticket_views` MODIFY `ticket_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void {}
};
