<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla pivot que vincula un Customer del Helpdesk con su identificador
 * en plataformas externas (PrestaShop, ERP Alvarez, Shopify, WooCommerce).
 *
 * Permite que un mismo customer tenga IDs distintos en cada plataforma
 * — caso típico Alvarez: IDCLIENTE en Oracle + id_customer en PrestaShop.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_customer_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('helpdesk_customers')
                ->cascadeOnDelete();
            $table->string('platform', 50);
            $table->string('external_id', 191);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_id'], 'helpdesk_cust_ext_platform_id_unique');
            $table->index(['customer_id', 'platform'], 'helpdesk_cust_ext_customer_platform_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_customer_external_ids');
    }
};
