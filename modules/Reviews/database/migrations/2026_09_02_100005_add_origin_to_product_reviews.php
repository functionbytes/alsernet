<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De dónde sale cada opinión.
 *
 * Hasta ahora todas venían de un cliente que compró en la tienda online. Con
 * las fichas de Google entra un segundo origen que no es equiparable: lo
 * escribió alguien en Google Maps sobre un establecimiento físico. Mezclarlos
 * en la misma media sería presentar como propio lo que no lo es, así que el
 * origen se guarda y se respeta en cada consulta.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            // product = opinión de un producto · store = opinión del negocio
            $table->string('entity', 16)->default('product')->after('id');
            // customer = quien compró · google = reseña externa
            $table->string('origin', 16)->default('customer')->after('entity');
            $table->foreignId('source_id')->nullable()->after('origin')
                ->constrained('review_sources')->nullOnDelete();
            // Identificador de la reseña en la plataforma de origen
            $table->string('external_id')->nullable()->after('source_id');
            $table->string('author_url')->nullable()->after('customer_email');

            $table->index(['entity', 'origin']);
        });

        // ps_comment_id solo existe para lo que vive en PrestaShop: una reseña
        // de Google recién leída todavía no está allí.
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            $table->unsignedInteger('ps_comment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('product_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_id');
            $table->dropIndex(['entity', 'origin']);
            $table->dropColumn(['entity', 'origin', 'external_id', 'author_url']);
        });
    }
};
