<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttributeChangesToActivityLogTable extends Migration
{
    public function up(): void
    {
        $tableName = config('activitylog.table_name');
        $connection = config('activitylog.database_connection');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (Schema::connection($connection)->hasColumn($tableName, 'attribute_changes')) {
            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->json('attribute_changes')->nullable()->after('properties');
        });
    }

    public function down(): void
    {
        $tableName = config('activitylog.table_name');
        $connection = config('activitylog.database_connection');

        if (Schema::connection($connection)->hasColumn($tableName, 'attribute_changes')) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                $table->dropColumn('attribute_changes');
            });
        }
    }
}
