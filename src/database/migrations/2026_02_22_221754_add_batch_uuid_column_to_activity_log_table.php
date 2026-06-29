<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up()
    {
        $tableName = config('activitylog.table_name');
        $connection = config('activitylog.database_connection');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (Schema::connection($connection)->hasColumn($tableName, 'batch_uuid')) {
            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) {
            $table->uuid('batch_uuid')->nullable()->after('properties');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropColumn('batch_uuid');
        });
    }
}
