<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_tickets')) {
            return;
        }

        Schema::connection('helpdesk')->table('helpdesk_tickets', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_tickets', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('rated_at');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_tickets', 'escalation_count')) {
                $table->unsignedTinyInteger('escalation_count')->default(0)->after('escalated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_tickets', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_tickets', 'escalated_at')) {
                $table->dropColumn('escalated_at');
            }

            if (Schema::connection('helpdesk')->hasColumn('helpdesk_tickets', 'escalation_count')) {
                $table->dropColumn('escalation_count');
            }
        });
    }
};
