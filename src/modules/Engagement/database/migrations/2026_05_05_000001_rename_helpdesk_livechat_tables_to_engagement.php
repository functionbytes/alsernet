<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Renames preserve all data, indexes, foreign keys.
     * Only runs if old tables exist (idempotent).
     */
    public function up(): void
    {
        $renames = $this->renameMap();

        foreach ($renames as $old => $new) {
            if (Schema::connection('helpdesk')->hasTable($old) && ! Schema::connection('helpdesk')->hasTable($new)) {
                Schema::connection('helpdesk')->rename($old, $new);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->renameMap() as $old => $new) {
            if (Schema::connection('helpdesk')->hasTable($new) && ! Schema::connection('helpdesk')->hasTable($old)) {
                Schema::connection('helpdesk')->rename($new, $old);
            }
        }
    }

    private function renameMap(): array
    {
        $oldPrefix = 'helpdesk_livechat_';
        $newPrefix = 'engagement_';

        $tables = [
            'events',
            'visitor_scores',
            'visitor_contexts',
            'trigger_rules',
            'personalization_rules',
            'recommendation_profiles',
            'catalog_products',
            'platform_integrations',
            'webhook_logs',
            'automation_flows',
            'automation_runs',
            'conversion_goals',
            'audit_logs',
        ];

        $map = [];
        foreach ($tables as $t) {
            $map[$oldPrefix.$t] = $newPrefix.$t;
        }

        return $map;
    }
};
