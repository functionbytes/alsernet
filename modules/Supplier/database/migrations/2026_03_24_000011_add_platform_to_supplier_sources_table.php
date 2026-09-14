<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_sources', function (Blueprint $table) {
            $table->string('platform', 50)->nullable()->after('source_type');
        });

        // Poblar platform desde label y usage_notes
        DB::statement("
            UPDATE supplier_sources SET platform = CASE
                WHEN label LIKE '%shopify%'                          THEN 'shopify'
                WHEN label LIKE '%prestashop%'
                     OR usage_notes LIKE '%PrestaShop%'             THEN 'prestashop'
                WHEN label LIKE '%woocommerce%'
                     OR usage_notes LIKE '%WooCommerce%'            THEN 'woocommerce'
                WHEN label LIKE '%magento%'
                     OR usage_notes LIKE '%Magento%'                THEN 'magento'
                WHEN label LIKE '%joomla%'                          THEN 'joomla'
                WHEN label LIKE '%bloqueado%'
                     OR label LIKE '%blocked%'
                     OR label LIKE '%timeout%'
                     OR label LIKE '%error%'                        THEN 'blocked'
                ELSE NULL
            END
            WHERE platform IS NULL
        ");

        // Regla especial: API type + priority=1 + activo + sin plataforma → shopify (solo supplier_id=16)
        DB::statement("
            UPDATE supplier_sources SET platform = 'shopify'
            WHERE platform IS NULL
              AND source_type = 'api'
              AND priority = 1
              AND is_active = 1
              AND supplier_id = 16
        ");
    }

    public function down(): void
    {
        Schema::table('supplier_sources', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
