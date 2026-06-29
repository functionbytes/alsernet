<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrate from multi-column boolean schema to per-channel rows.
     *
     * Old schema: (user_id, notification_type) unique + email_enabled/push_enabled/in_app_enabled booleans
     * New schema: (user_id, notification_type, channel) unique + single `enabled` boolean per row
     */
    public function up(): void
    {
        // Guard: already migrated if email_enabled is gone
        if (! Schema::hasColumn('notification_settings', 'email_enabled')) {
            return;
        }

        // Step 1: Add new columns as nullable to allow populating data first
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->string('channel')->nullable()->after('notification_type');
            $table->boolean('enabled')->nullable()->after('channel');
        });

        // Step 2: Expand each row into 3 per-channel rows, delete originals
        $this->expandToChannelRows();

        // Step 3: Make new columns non-nullable, drop legacy columns, swap unique index
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->string('channel')->nullable(false)->default('')->change();
            $table->boolean('enabled')->nullable(false)->default(true)->change();
        });

        $this->dropIndexByColumns('notification_settings', ['user_id', 'notification_type']);

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['email_enabled', 'push_enabled', 'in_app_enabled', 'frequency', 'preferences', 'opted_out_at']);
            $table->unique(['user_id', 'notification_type', 'channel']);
            $table->index('notification_type');
        });
    }

    /**
     * Reverse to the multi-column boolean schema.
     */
    public function down(): void
    {
        // Guard: already reversed if channel column is gone
        if (! Schema::hasColumn('notification_settings', 'channel')) {
            return;
        }

        // Guard: avoid re-adding legacy columns on a partial failure retry
        if (! Schema::hasColumn('notification_settings', 'email_enabled')) {
            Schema::table('notification_settings', function (Blueprint $table) {
                $table->boolean('email_enabled')->nullable()->after('notification_type');
                $table->boolean('push_enabled')->nullable()->after('email_enabled');
                $table->boolean('in_app_enabled')->nullable()->after('push_enabled');
                $table->string('frequency')->nullable()->after('in_app_enabled');
                $table->text('preferences')->nullable()->after('frequency');
                $table->timestamp('opted_out_at')->nullable()->after('preferences');
            });
        }

        // Step 2: Collapse per-channel rows into single legacy rows, delete per-channel originals
        $this->collapseChannelRows();

        // Step 3: Make legacy columns non-nullable with defaults
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->boolean('email_enabled')->nullable(false)->default(true)->change();
            $table->boolean('push_enabled')->nullable(false)->default(false)->change();
            $table->boolean('in_app_enabled')->nullable(false)->default(true)->change();
            $table->string('frequency')->nullable(false)->default('instant')->change();
        });

        // Drop the (user_id, notification_type, channel) unique + notification_type index by actual name
        $this->dropIndexByColumns('notification_settings', ['user_id', 'channel', 'notification_type']);
        $this->dropIndexByColumns('notification_settings', ['notification_type']);

        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['channel', 'enabled']);
            $table->unique(['user_id', 'notification_type']);
            $table->index('notification_type');
        });
    }

    /**
     * Expand each legacy row into three per-channel rows, then delete the originals.
     */
    private function expandToChannelRows(): void
    {
        $channelMap = [
            'email' => 'email_enabled',
            'push' => 'push_enabled',
            'in_app' => 'in_app_enabled',
        ];

        DB::table('notification_settings')->whereNull('channel')->chunkById(500, function ($rows) use ($channelMap) {
            $inserts = [];

            foreach ($rows as $row) {
                foreach ($channelMap as $channel => $legacyColumn) {
                    $inserts[] = [
                        'user_id' => $row->user_id,
                        'notification_type' => $row->notification_type,
                        'channel' => $channel,
                        'enabled' => (int) $row->{$legacyColumn},
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }
            }

            DB::table('notification_settings')->insert($inserts);
        });

        DB::table('notification_settings')->whereNull('channel')->delete();
    }

    /**
     * Collapse per-channel rows back into single legacy rows.
     */
    private function collapseChannelRows(): void
    {
        $groups = DB::table('notification_settings')
            ->whereNotNull('channel')
            ->get()
            ->groupBy(fn ($row) => $row->user_id.'|'.$row->notification_type);

        $inserts = [];

        foreach ($groups as $rows) {
            $base = $rows->first();
            $byChannel = $rows->keyBy('channel');

            $inserts[] = [
                'user_id' => $base->user_id,
                'notification_type' => $base->notification_type,
                'email_enabled' => (int) optional($byChannel->get('email'))->enabled,
                'push_enabled' => (int) optional($byChannel->get('push'))->enabled,
                'in_app_enabled' => (int) optional($byChannel->get('in_app'))->enabled,
                'frequency' => 'instant',
                'preferences' => null,
                'opted_out_at' => null,
                'created_at' => $base->created_at,
                'updated_at' => $base->updated_at,
            ];
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('notification_settings')->insert($chunk);
        }

        DB::table('notification_settings')->whereNotNull('channel')->delete();
    }

    /**
     * Drop an index by its column set, regardless of the auto-generated name.
     * Silently skips if no matching index is found.
     */
    private function dropIndexByColumns(string $table, array $columns): void
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}`");

        $keyColumns = [];
        foreach ($indexes as $row) {
            $keyColumns[$row->Key_name][] = $row->Column_name;
        }

        sort($columns);

        foreach ($keyColumns as $keyName => $keyCols) {
            sort($keyCols);
            if ($keyCols === $columns) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$keyName}`");

                return;
            }
        }
    }
};
