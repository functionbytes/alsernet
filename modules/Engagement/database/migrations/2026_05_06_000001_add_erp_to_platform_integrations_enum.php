<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende el enum `platform` de `engagement_platform_integrations` para
 * incluir 'erp'. Necesario para el caso multi-fuente Alvarez (PrestaShop
 * + ERP propio Oracle).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('engagement_platform_integrations')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE engagement_platform_integrations
             MODIFY COLUMN platform ENUM('prestashop', 'shopify', 'woocommerce', 'erp', 'custom') NOT NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('engagement_platform_integrations')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE engagement_platform_integrations
             MODIFY COLUMN platform ENUM('prestashop', 'shopify', 'woocommerce', 'custom') NOT NULL"
        );
    }
};
