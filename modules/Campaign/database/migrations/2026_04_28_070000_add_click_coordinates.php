<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_click_logs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('click_x')->nullable()->after('ip');
            $table->unsignedSmallInteger('click_y')->nullable()->after('click_x');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_click_logs', function (Blueprint $table): void {
            $table->dropColumn(['click_x', 'click_y']);
        });
    }
};
