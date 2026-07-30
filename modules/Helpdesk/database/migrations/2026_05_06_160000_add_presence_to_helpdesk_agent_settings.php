<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_agent_settings', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'presence_state')) {
                $table->enum('presence_state', ['available', 'busy', 'away', 'offline'])->default('offline')->after('preferences');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'presence_changed_at')) {
                $table->timestamp('presence_changed_at')->nullable()->after('presence_state');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'last_heartbeat_at')) {
                $table->timestamp('last_heartbeat_at')->nullable()->after('presence_changed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_agent_settings', function (Blueprint $table) {
            $table->dropColumn(['presence_state', 'presence_changed_at', 'last_heartbeat_at']);
        });
    }
};
