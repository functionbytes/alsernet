<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('supplier_sync_schedules')->truncate();

        Schema::table('supplier_sync_schedules', function (Blueprint $table) {
            $table->tinyInteger('hour')->unsigned()->after('label');
            $table->tinyInteger('minute')->unsigned()->default(0)->after('hour');
            $table->dropColumn('schedule_hours');
            $table->index(['sync_type', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('supplier_sync_schedules', function (Blueprint $table) {
            $indexName = 'supplier_sync_schedules_sync_type_is_enabled_index';
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_keys($sm->listTableIndexes('supplier_sync_schedules'));

            if (in_array($indexName, $indexes)) {
                $table->dropIndex($indexName);
            }

            $table->json('schedule_hours')->nullable()->after('label');
            $table->dropColumn(['hour', 'minute']);
        });
    }
};
