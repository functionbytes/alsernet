<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Rename ai-agent columns to reflect their real purpose:
     * - `backups`     → `parameters` (model temperature/max_tokens/...)
     * - `personality` → contents copied into existing `system_prompt`
     *
     * Adds `is_default` to clarify single-default-agent semantics
     * without removing multi-agent support.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ai_agents')) {
            return;
        }

        $schema->table('helpdesk_ai_agents', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_ai_agents', 'parameters')) {
                $table->json('parameters')->nullable()->after('model');
            }

            if (! $schema->hasColumn('helpdesk_ai_agents', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('status')->index();
            }
        });

        if ($schema->hasColumn('helpdesk_ai_agents', 'backups')) {
            DB::connection($this->connection)
                ->statement('UPDATE helpdesk_ai_agents SET parameters = backups WHERE parameters IS NULL AND backups IS NOT NULL');
        }

        if ($schema->hasColumn('helpdesk_ai_agents', 'personality')
            && $schema->hasColumn('helpdesk_ai_agents', 'system_prompt')) {
            DB::connection($this->connection)
                ->statement('UPDATE helpdesk_ai_agents SET system_prompt = personality WHERE (system_prompt IS NULL OR system_prompt = "") AND personality IS NOT NULL');
        }

        $hasDefault = DB::connection($this->connection)
            ->table('helpdesk_ai_agents')
            ->where('is_default', true)
            ->exists();

        if (! $hasDefault) {
            $firstId = DB::connection($this->connection)
                ->table('helpdesk_ai_agents')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->value('id');

            if ($firstId) {
                DB::connection($this->connection)
                    ->table('helpdesk_ai_agents')
                    ->where('id', $firstId)
                    ->update(['is_default' => true]);
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_ai_agents')) {
            return;
        }

        $schema->table('helpdesk_ai_agents', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('helpdesk_ai_agents', 'parameters')) {
                $table->dropColumn('parameters');
            }
            if ($schema->hasColumn('helpdesk_ai_agents', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
