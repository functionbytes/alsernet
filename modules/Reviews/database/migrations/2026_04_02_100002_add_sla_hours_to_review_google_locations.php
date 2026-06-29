<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_google_locations', function (Blueprint $table) {
            $table->unsignedSmallInteger('sla_hours')
                ->nullable()
                ->after('synced_at')
                ->comment('Hours allowed to reply before SLA breach (null = no SLA)');
        });
    }

    public function down(): void
    {
        Schema::table('review_google_locations', function (Blueprint $table) {
            $table->dropColumn('sla_hours');
        });
    }
};
