<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('engagement_visitor_scores', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('engagement_visitor_scores', 'last_event_at')) {
                $table->timestamp('last_event_at')->nullable()->after('segment');
            }
            if (! Schema::connection('helpdesk')->hasColumn('engagement_visitor_scores', 'last_recalc_at')) {
                $table->timestamp('last_recalc_at')->nullable()->after('last_event_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('engagement_visitor_scores', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('engagement_visitor_scores', 'last_event_at')) {
                $table->dropColumn('last_event_at');
            }
            if (Schema::connection('helpdesk')->hasColumn('engagement_visitor_scores', 'last_recalc_at')) {
                $table->dropColumn('last_recalc_at');
            }
        });
    }
};
