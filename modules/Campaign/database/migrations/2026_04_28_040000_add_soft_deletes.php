<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft deletes en los modelos críticos: campaigns, campaign_maillists,
 * campaign_subscribers. Permite recuperar borrados accidentales y mantener
 * la traza histórica para reporting.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['campaigns', 'campaign_maillists', 'campaign_subscribers', 'campaign_templates'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (['campaigns', 'campaign_maillists', 'campaign_subscribers', 'campaign_templates'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->dropSoftDeletes();
            });
        }
    }
};
