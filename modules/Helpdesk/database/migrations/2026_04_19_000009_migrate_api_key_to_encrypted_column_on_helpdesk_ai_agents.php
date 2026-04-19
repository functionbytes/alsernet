<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\AiAgent;

return new class extends Migration
{
    /**
     * Migrate plaintext api_key from the backups JSON column into the
     * dedicated api_key_encrypted column (encrypted via the model cast).
     * The key is then removed from backups to avoid plaintext duplication.
     *
     * No-op when the table doesn't yet have `backups` — lets environments
     * that still lack the column migrate safely and re-run later.
     */
    public function up(): void
    {
        if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'backups')) {
            return;
        }

        AiAgent::query()
            ->whereNotNull('backups')
            ->each(function (AiAgent $agent): void {
                $backups = $agent->backups ?? [];

                if (empty($backups['api_key'])) {
                    return;
                }

                // Assigning through the model triggers the 'encrypted' cast.
                $agent->api_key_encrypted = $backups['api_key'];

                unset($backups['api_key']);
                $agent->backups = $backups;

                $agent->saveQuietly();
            });
    }

    public function down(): void
    {
        if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ai_agents', 'backups')) {
            return;
        }

        AiAgent::query()
            ->whereNotNull('api_key_encrypted')
            ->each(function (AiAgent $agent): void {
                $apiKey = $agent->getApiKey(); // decrypts via cast

                if (! $apiKey) {
                    return;
                }

                $backups = $agent->backups ?? [];
                $backups['api_key'] = $apiKey;

                $agent->backups = $backups;
                $agent->api_key_encrypted = null;

                $agent->saveQuietly();
            });
    }
};
