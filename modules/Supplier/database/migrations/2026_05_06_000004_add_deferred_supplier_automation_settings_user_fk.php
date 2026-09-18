<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recover the FK constraint on supplier_automation_settings.updated_by → users.id
 * when it was not added at table creation time (typically because the users
 * table did not exist yet under RefreshDatabase ordering).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_automation_settings') || ! Schema::hasTable('users')) {
            return;
        }

        $existing = collect(DB::select('SHOW INDEX FROM supplier_automation_settings'))
            ->pluck('Key_name')
            ->unique()
            ->toArray();

        if (in_array('supplier_automation_settings_updated_by_foreign', $existing, true)) {
            return;
        }

        // Null out orphaned references before adding the FK.
        DB::statement(
            'UPDATE supplier_automation_settings sas
             LEFT JOIN users u ON u.id = sas.updated_by
             SET sas.updated_by = NULL
             WHERE sas.updated_by IS NOT NULL AND u.id IS NULL'
        );

        Schema::table('supplier_automation_settings', function (Blueprint $table) {
            $table->foreign('updated_by', 'supplier_automation_settings_updated_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_automation_settings')) {
            return;
        }

        Schema::table('supplier_automation_settings', function (Blueprint $table) {
            $table->dropForeign('supplier_automation_settings_updated_by_foreign');
        });
    }
};
