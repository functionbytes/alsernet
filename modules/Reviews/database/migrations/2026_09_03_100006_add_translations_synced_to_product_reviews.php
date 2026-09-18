<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuándo se miró por última vez qué traducciones tiene esta opinión en la tienda.
 *
 * El 81 % de las opiniones ya venían traducidas de antes, y la ficha decía
 * "0 de 5 idiomas". Se consultan la primera vez que se abre la ficha, y esta
 * marca evita repetir la llamada en cada visita.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            $table->timestamp('translations_synced_at')->nullable()->after('translated_at');
        });

        Schema::connection($this->connection)->table('product_review_translations', function (Blueprint $table) {
            // 'prestashop' = heredada de la tienda, sin pasar por aquí.
            $table->boolean('inherited')->default(false)->after('provider');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            $table->dropColumn('translations_synced_at');
        });

        Schema::connection($this->connection)->table('product_review_translations', function (Blueprint $table) {
            $table->dropColumn('inherited');
        });
    }
};
