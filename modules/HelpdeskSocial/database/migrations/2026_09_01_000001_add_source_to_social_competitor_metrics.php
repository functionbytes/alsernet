<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_social_competitor_metrics', function (Blueprint $table) {
            // 'simulated' (SyncCompetitorMetricsJob, demo_mode) vs 'real' (futura
            // integracion con la API del competidor). Todas las filas existentes
            // son simuladas: es el unico origen que ha existido hasta ahora.
            $table->string('source', 16)->default('simulated')->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_competitor_metrics', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
