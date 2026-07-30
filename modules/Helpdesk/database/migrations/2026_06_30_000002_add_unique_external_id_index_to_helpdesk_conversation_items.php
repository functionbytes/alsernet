<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * CHAN-02: enforce ingestion idempotency via a UNIQUE index on
     * helpdesk_conversation_items.external_id.
     *
     * Safety: external_id is nullable (internal notes/agent replies carry NULL,
     * which MySQL/MariaDB permits multiple of under a unique index) and prior
     * ingestion was not idempotent, so non-null duplicates may already exist.
     * A blind unique would fail on deploy. This migration therefore adds the
     * unique index ONLY when no duplicate non-null external_id is present; if
     * duplicates exist it leaves the existing non-unique index untouched and
     * the unique must be applied after de-duplication (see needs_followup).
     */
    public function up(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversation_items')) {
            return;
        }

        if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversation_items', 'external_id')) {
            return;
        }

        if ($this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_external_id_unique')) {
            return;
        }

        if ($this->hasDuplicateExternalIds()) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            $table->unique('external_id', 'helpdesk_conv_items_external_id_unique');

            if ($this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_external_id_index')) {
                $table->dropIndex('helpdesk_conv_items_external_id_index');
            }
        });
    }

    public function down(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (! Schema::connection($this->connection)->hasTable('helpdesk_conversation_items')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table) {
            if ($this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_external_id_unique')) {
                $table->dropUnique('helpdesk_conv_items_external_id_unique');
            }

            if (! $this->hasIndex('helpdesk_conversation_items', 'helpdesk_conv_items_external_id_index')) {
                $table->index('external_id', 'helpdesk_conv_items_external_id_index');
            }
        });
    }

    private function hasDuplicateExternalIds(): bool
    {
        return DB::connection($this->connection)
            ->table('helpdesk_conversation_items')
            ->whereNotNull('external_id')
            ->select('external_id')
            ->groupBy('external_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::connection($this->connection)->getIndexes($table))
            ->pluck('name')
            ->contains($index);
    }
};
