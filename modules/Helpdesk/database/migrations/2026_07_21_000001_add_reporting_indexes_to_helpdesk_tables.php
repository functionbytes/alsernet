<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para las queries de reports/insights:
 * - csat_ratings.customer_id: filtros de CustomerInsightsService (health score,
 *   lifetime metrics, journey) y report at-risk.
 * - conversation_tag_pivot (tag_id, created_at): joins por tag de sentimiento
 *   negativo con ventana temporal (at-risk, insights); los índices existentes
 *   lideran por conversation_id y no sirven a esas queries.
 * - conversation_items (user_id, type, created_at): groupBy por agente de
 *   AgentPerformance/Analytics y filtros whereNull(user_id)+type+fecha de
 *   Heatmap/Trends.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_csat_ratings', function (Blueprint $table): void {
            $table->index('customer_id', 'hcr_customer_id_index');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_tag_pivot', function (Blueprint $table): void {
            $table->index(['tag_id', 'created_at'], 'hctp_tag_created_index');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->index(['user_id', 'type', 'created_at'], 'hci_user_type_created_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_csat_ratings', function (Blueprint $table): void {
            $table->dropIndex('hcr_customer_id_index');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_tag_pivot', function (Blueprint $table): void {
            $table->dropIndex('hctp_tag_created_index');
        });

        Schema::connection($this->connection)->table('helpdesk_conversation_items', function (Blueprint $table): void {
            $table->dropIndex('hci_user_type_created_index');
        });
    }
};
