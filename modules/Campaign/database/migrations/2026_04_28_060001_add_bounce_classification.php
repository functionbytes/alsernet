<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_tracking_logs', function (Blueprint $table): void {
            $table->enum('bounce_type', ['hard', 'soft', 'block', 'unknown'])->nullable()->after('status');
            $table->string('bounce_category', 50)->nullable()->after('bounce_type');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_tracking_logs', function (Blueprint $table): void {
            $table->dropColumn(['bounce_type', 'bounce_category']);
        });
    }
};
