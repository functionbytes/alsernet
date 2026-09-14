<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_agent_settings', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('auto_assign');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'vacation_until')) {
                $table->timestamp('vacation_until')->nullable()->after('is_available');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_agent_settings', 'current_open_count')) {
                $table->unsignedInteger('current_open_count')->default(0)->after('vacation_until');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_agent_settings', function (Blueprint $table): void {
            $table->dropColumn(['is_available', 'vacation_until', 'current_open_count']);
        });
    }
};
