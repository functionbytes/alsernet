<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_automations', function (Blueprint $table) {
            $table->string('goal_event', 60)->nullable()->after('trigger_config');
            $table->unsignedInteger('goal_window_hours')->nullable()->after('goal_event');
        });

        Schema::table('remarketing_automation_runs', function (Blueprint $table) {
            $table->timestamp('goal_reached_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_automation_runs', function (Blueprint $table) {
            $table->dropColumn('goal_reached_at');
        });

        Schema::table('remarketing_automations', function (Blueprint $table) {
            $table->dropColumn(['goal_event', 'goal_window_hours']);
        });
    }
};
