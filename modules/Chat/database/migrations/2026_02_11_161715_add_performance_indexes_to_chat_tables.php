<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('chat_customers', ['email'], 'hd_customers_email');
        $this->addIndex('chat_customers', ['phone_number'], 'hd_customers_phone');
        $this->addIndex('chat_customers', ['last_activity_at'], 'hd_customers_last_activity');
        $this->addIndex('chat_customers', ['blocked'], 'hd_customers_blocked');

        $this->addIndex('chat_conversations', ['assignee_id', 'status_id'], 'hd_conv_assignee_status');
        $this->addIndex('chat_conversations', ['team_id', 'status_id'], 'hd_conv_team_status');
        $this->addIndex('chat_conversations', ['last_activity_at', 'status_id'], 'hd_conv_activity_status');
    }

    public function down(): void
    {
        Schema::table('chat_customers', function (Blueprint $table) {
            $table->dropIndex('hd_customers_email');
            $table->dropIndex('hd_customers_phone');
            $table->dropIndex('hd_customers_last_activity');
            $table->dropIndex('hd_customers_blocked');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropIndex('hd_conv_assignee_status');
            $table->dropIndex('hd_conv_team_status');
            $table->dropIndex('hd_conv_activity_status');
        });
    }

    private function addIndex(string $table, array $columns, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        )->c > 0;
    }
};
