<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogo configurable de proveedores de integracion externa (PrestaShop,
 * ERP y proveedores custom). No tiene FK hacia helpdesk_customer_external_ids
 * — la relacion es por el string `platform`, igual que el resto del sistema,
 * para tolerar plataformas no catalogadas o desactivadas sin romper vinculos
 * ya existentes.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_integration_providers', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50)->unique();
            $table->string('driver', 50)->nullable();
            $table->string('label', 100);
            $table->string('icon', 100)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_linkable')->default(true);
            $table->json('search_types')->nullable();
            // text (no json): el cast encrypted:array siempre guarda ciphertext,
            // que no es JSON valido y rompe el CHECK JSON_VALID() de una columna json().
            $table->text('credentials')->nullable();
            $table->json('config')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_linkable');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_integration_providers');
    }
};
