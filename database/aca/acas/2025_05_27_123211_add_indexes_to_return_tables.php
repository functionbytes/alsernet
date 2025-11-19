<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddTrackingFieldsToReturnRequests extends Migration
{
    public function up()
    {
        Schema::table('return_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('return_requests', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('logistics_mode');
                $table->index('tracking_number');
            }

            if (!Schema::hasColumn('return_requests', 'carrier_id')) {
                $table->unsignedBigInteger('carrier_id')->nullable()->after('tracking_number');
            }

            if (!Schema::hasColumn('return_requests', 'pickup_scheduled_at')) {
                $table->timestamp('pickup_scheduled_at')->nullable()->after('pickup_date');
            }

            if (!Schema::hasColumn('return_requests', 'package_received_at')) {
                $table->timestamp('package_received_at')->nullable()->after('received_date');
            }
        });
    }

    public function down()
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number',
                'carrier_id',
                'pickup_scheduled_at',
                'package_received_at'
            ]);
        });
    }
}
