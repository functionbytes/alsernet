<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_live_view')) {
                $table->boolean('enable_live_view')->default(false)->after('enforce_identity_verification');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_screen_share')) {
                $table->boolean('enable_screen_share')->default(false)->after('enable_live_view');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            foreach (['enable_screen_share', 'enable_live_view'] as $col) {
                if (Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
