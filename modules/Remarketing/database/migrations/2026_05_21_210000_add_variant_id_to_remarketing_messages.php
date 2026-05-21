<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Remarketing\Models\CampaignVariant;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_messages', function (Blueprint $table) {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('remarketing_campaign_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_messages', function (Blueprint $table) {
            $table->dropForeignIdFor(CampaignVariant::class);
            $table->dropColumn('variant_id');
        });
    }
};
