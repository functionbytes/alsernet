<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_campaigns', function (Blueprint $table) {
            $table->string('ab_winner_metric', 20)->nullable()->after('settings')
                ->comment('open_rate|ctr|revenue — metric used to pick A/B winner');
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_campaigns', function (Blueprint $table) {
            $table->dropColumn('ab_winner_metric');
        });
    }
};
