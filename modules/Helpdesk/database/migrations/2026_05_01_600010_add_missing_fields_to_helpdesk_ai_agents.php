<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ai_agents', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('active');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'system_prompt')) {
                $table->longText('system_prompt')->nullable()->after('description');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'knowledge_sources')) {
                $table->json('knowledge_sources')->nullable()->after('system_prompt');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'enabled_channels')) {
                $table->json('enabled_channels')->nullable()->after('knowledge_sources');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'escalation_keywords')) {
                $table->json('escalation_keywords')->nullable()->after('enabled_channels');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'max_messages_before_escalation')) {
                $table->unsignedSmallInteger('max_messages_before_escalation')->default(5)->after('escalation_keywords');
            }
        });

        // Sync is_active from active for existing rows
        DB::connection('helpdesk')->statement('UPDATE helpdesk_ai_agents SET is_active = active WHERE is_active = 0 AND active = 1');
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ai_agents', function (Blueprint $table) {
            $table->dropColumnIfExists('is_active');
            $table->dropColumnIfExists('system_prompt');
            $table->dropColumnIfExists('knowledge_sources');
            $table->dropColumnIfExists('enabled_channels');
            $table->dropColumnIfExists('escalation_keywords');
            $table->dropColumnIfExists('max_messages_before_escalation');
        });
    }
};
