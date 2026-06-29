<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        // Migrate existing boolean values before changing column type
        DB::connection($this->connection)->statement(
            "UPDATE helpdesk_agent_settings SET accepts_conversations = 'yes' WHERE accepts_conversations = '1'"
        );
        DB::connection($this->connection)->statement(
            "UPDATE helpdesk_agent_settings SET accepts_conversations = 'no' WHERE accepts_conversations = '0'"
        );

        // Change accepts_conversations from tinyint to varchar(20)
        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_agent_settings MODIFY accepts_conversations VARCHAR(20) NOT NULL DEFAULT 'yes'"
        );

        // Add working_hours JSON column if it doesn't exist
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'working_hours')) {
            Schema::connection($this->connection)->table('helpdesk_agent_settings', function (Blueprint $table): void {
                $table->json('working_hours')->nullable()->after('accepts_conversations');
            });
        }
    }

    public function down(): void
    {
        // Revert working_hours values that might break boolean conversion
        DB::connection($this->connection)->statement(
            "UPDATE helpdesk_agent_settings SET accepts_conversations = 'yes' WHERE accepts_conversations = 'working_hours'"
        );

        // Revert varchar back to tinyint (yes→1, no→0)
        DB::connection($this->connection)->statement(
            "UPDATE helpdesk_agent_settings SET accepts_conversations = '1' WHERE accepts_conversations = 'yes'"
        );
        DB::connection($this->connection)->statement(
            "UPDATE helpdesk_agent_settings SET accepts_conversations = '0' WHERE accepts_conversations = 'no'"
        );

        DB::connection($this->connection)->statement(
            'ALTER TABLE helpdesk_agent_settings MODIFY accepts_conversations TINYINT(1) NOT NULL DEFAULT 1'
        );

        // Drop working_hours
        if (Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'working_hours')) {
            Schema::connection($this->connection)->table('helpdesk_agent_settings', function (Blueprint $table): void {
                $table->dropColumn('working_hours');
            });
        }
    }
};
