<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds columns required by the module seeders that were not present in the
 * original table-creation migrations.
 *
 * All additions are guarded with hasColumn() so this migration is idempotent.
 *
 * Tables touched:
 *  - helpdesk_ticket_views:      slug, icon, color
 *  - helpdesk_ticket_categories: uid, key, position
 *  - helpdesk_ticket_statuses:   type, uid, is_active
 *    (key/icon/is_closed/position already added by 2026_04_19_000001)
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // ── helpdesk_ticket_views ──────────────────────────────────────────
        if ($schema->hasTable('helpdesk_ticket_views')) {
            $schema->table('helpdesk_ticket_views', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('helpdesk_ticket_views', 'slug')) {
                    // nullable — existing rows have no slug yet; unique index added below
                    $table->string('slug')->nullable()->after('name');
                }
                if (! $schema->hasColumn('helpdesk_ticket_views', 'icon')) {
                    $table->string('icon')->nullable();
                }
                if (! $schema->hasColumn('helpdesk_ticket_views', 'color')) {
                    $table->string('color')->nullable();
                }
                // System/shared views (seeded by TicketViewSeeder) have no owner
                if ($schema->hasColumn('helpdesk_ticket_views', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                }
            });

            // Add unique index on slug only when the column exists and the index does not
            $this->addUniqueIndexSafe('helpdesk_ticket_views', 'slug', 'ticket_views_slug_unique');
        }

        // ── helpdesk_ticket_categories ────────────────────────────────────
        if ($schema->hasTable('helpdesk_ticket_categories')) {
            $schema->table('helpdesk_ticket_categories', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('helpdesk_ticket_categories', 'uid')) {
                    $table->string('uid', 36)->nullable()->after('id');
                }
                if (! $schema->hasColumn('helpdesk_ticket_categories', 'key')) {
                    $table->string('key')->nullable()->after('uid');
                }
                if (! $schema->hasColumn('helpdesk_ticket_categories', 'position')) {
                    $table->integer('position')->default(0);
                }
            });

            $this->addUniqueIndexSafe('helpdesk_ticket_categories', 'uid', 'ticket_categories_uid_unique');
            $this->addIndexSafe('helpdesk_ticket_categories', 'key', 'ticket_categories_key_index');
        }

        // ── helpdesk_ticket_statuses ──────────────────────────────────────
        // key / icon / is_closed / position were added by 2026_04_19_000001.
        // We only add the columns StatusesSeeder still needs: type, uid, is_active.
        if ($schema->hasTable('helpdesk_ticket_statuses')) {
            $schema->table('helpdesk_ticket_statuses', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('helpdesk_ticket_statuses', 'type')) {
                    $table->string('type')->nullable()->default(null);
                }
                if (! $schema->hasColumn('helpdesk_ticket_statuses', 'uid')) {
                    $table->string('uid', 36)->nullable();
                }
                if (! $schema->hasColumn('helpdesk_ticket_statuses', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_views')) {
            $schema->table('helpdesk_ticket_views', function (Blueprint $table) use ($schema) {
                $this->dropIndexSafe($table, 'ticket_views_slug_unique', 'helpdesk_ticket_views');
                if ($schema->hasColumn('helpdesk_ticket_views', 'slug')) {
                    $table->dropColumn('slug');
                }
                if ($schema->hasColumn('helpdesk_ticket_views', 'icon')) {
                    $table->dropColumn('icon');
                }
                if ($schema->hasColumn('helpdesk_ticket_views', 'color')) {
                    $table->dropColumn('color');
                }
            });
        }

        if ($schema->hasTable('helpdesk_ticket_categories')) {
            $schema->table('helpdesk_ticket_categories', function (Blueprint $table) use ($schema) {
                $this->dropIndexSafe($table, 'ticket_categories_uid_unique', 'helpdesk_ticket_categories');
                $this->dropIndexSafe($table, 'ticket_categories_key_index', 'helpdesk_ticket_categories');
                if ($schema->hasColumn('helpdesk_ticket_categories', 'uid')) {
                    $table->dropColumn('uid');
                }
                if ($schema->hasColumn('helpdesk_ticket_categories', 'key')) {
                    $table->dropColumn('key');
                }
                if ($schema->hasColumn('helpdesk_ticket_categories', 'position')) {
                    $table->dropColumn('position');
                }
            });
        }

        if ($schema->hasTable('helpdesk_ticket_statuses')) {
            $schema->table('helpdesk_ticket_statuses', function (Blueprint $table) use ($schema) {
                if ($schema->hasColumn('helpdesk_ticket_statuses', 'type')) {
                    $table->dropColumn('type');
                }
                if ($schema->hasColumn('helpdesk_ticket_statuses', 'uid')) {
                    $table->dropColumn('uid');
                }
                if ($schema->hasColumn('helpdesk_ticket_statuses', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function addUniqueIndexSafe(string $table, string $column, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            Schema::connection($this->connection)->table($table, function (Blueprint $t) use ($column, $indexName) {
                $t->unique($column, $indexName);
            });
        }
    }

    private function addIndexSafe(string $table, string $column, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            Schema::connection($this->connection)->table($table, function (Blueprint $t) use ($column, $indexName) {
                $t->index($column, $indexName);
            });
        }
    }

    private function dropIndexSafe(Blueprint $table, string $indexName, string $tableName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            $table->dropIndex($indexName);
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(
            DB::connection($this->connection)
                ->select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName])
        )->isNotEmpty();
    }
};
