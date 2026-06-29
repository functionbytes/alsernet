<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('engagement_automation_runs', function (Blueprint $table) {
            $table->unique(['flow_id', 'session_token'], 'engagement_automation_runs_flow_session_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('engagement_automation_runs', function (Blueprint $table) {
            $table->dropUnique('engagement_automation_runs_flow_session_unique');
        });
    }
};
