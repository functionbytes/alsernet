<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the helpdesk_campaigns schema with what the Campaign model and
 * controllers expect: published_at/ends_at (instead of started_at/ended_at),
 * description, content (json), appearance (json), conditions (json).
 *
 * Existing data in started_at/ended_at is preserved by copying into the new
 * columns before dropping the old ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('helpdesk');
        $conn = DB::connection('helpdesk');

        $schema->table('helpdesk_campaigns', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_campaigns', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('published_at');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'content')) {
                $table->longText('content')->nullable()->after('ends_at');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'appearance')) {
                $table->longText('appearance')->nullable()->after('content');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'conditions')) {
                $table->longText('conditions')->nullable()->after('appearance');
            }
        });

        // Backfill from legacy columns if present
        if ($schema->hasColumn('helpdesk_campaigns', 'started_at')) {
            $conn->statement('UPDATE helpdesk_campaigns SET published_at = started_at WHERE published_at IS NULL AND started_at IS NOT NULL');
        }
        if ($schema->hasColumn('helpdesk_campaigns', 'ended_at')) {
            $conn->statement('UPDATE helpdesk_campaigns SET ends_at = ended_at WHERE ends_at IS NULL AND ended_at IS NOT NULL');
        }
        if ($schema->hasColumn('helpdesk_campaigns', 'targeting_rules')) {
            $conn->statement('UPDATE helpdesk_campaigns SET conditions = targeting_rules WHERE conditions IS NULL AND targeting_rules IS NOT NULL');
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('helpdesk');

        $schema->table('helpdesk_campaigns', function (Blueprint $table) use ($schema) {
            foreach (['conditions', 'appearance', 'content', 'ends_at', 'published_at'] as $col) {
                if ($schema->hasColumn('helpdesk_campaigns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
