<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos que no deben viajar al ticket de Helpdesk.
 *
 * La selección de deportes y el consentimiento comercial son datos de gestión:
 * ya van por el circuito de LOPD/newsletter al ERP y al agente que atiende el
 * ticket no le sirven de nada. Estaban excluidos en una constante del módulo de
 * PrestaShop, así que añadir otro campo obligaba a tocar PHP y desplegar; ahora
 * es una propiedad del campo, editable desde el panel, que viaja en fields_meta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->boolean('exclude_from_helpdesk')->default(false)->after('is_visible');
        });

        // Lo que hoy excluye la constante del módulo de la tienda.
        Schema::getConnection()
            ->table('form_fields')
            ->whereIn('key', ['sports', 'services'])
            ->update(['exclude_from_helpdesk' => true]);
    }

    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('exclude_from_helpdesk');
        });
    }
};
