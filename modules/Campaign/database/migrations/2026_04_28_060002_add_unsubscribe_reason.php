<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_unsubscribe_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('campaign_unsubscribe_logs', 'reason_detail')) {
                $table->string('reason_detail', 255)->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaign_unsubscribe_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('campaign_unsubscribe_logs', 'reason_detail')) {
                $table->dropColumn('reason_detail');
            }
        });
    }
};
