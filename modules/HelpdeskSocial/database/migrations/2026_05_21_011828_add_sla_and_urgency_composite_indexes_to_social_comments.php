<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            // SLA response breach scan
            $table->index(
                ['sla_response_deadline', 'sla_response_breached', 'first_response_at'],
                'social_comments_sla_response_breach_idx'
            );

            // SLA resolution breach scan
            $table->index(
                ['sla_resolution_deadline', 'sla_resolution_breached', 'status'],
                'social_comments_sla_resolution_breach_idx'
            );

            // Urgency sort path in the inbox
            $table->index('urgency', 'social_comments_urgency_idx');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->dropIndex('social_comments_sla_response_breach_idx');
            $table->dropIndex('social_comments_sla_resolution_breach_idx');
            $table->dropIndex('social_comments_urgency_idx');
        });
    }
};
