<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            // Índice para búsquedas por order_id
            $table->index('order_id')->comment('Index for order_id searches');

            // Índice para búsquedas por reference
            $table->index('reference')->comment('Index for reference searches');

            // Índice para búsquedas por customer_id (relación con cliente)
            $table->index('customer_id')->comment('Index for customer_id joins');

            // Índice compuesto para filtro de upload_at y proccess
            $table->index(['upload_at', 'proccess'])->comment('Index for upload status and process filtering');

            // Índice para source (nuevo campo)
            $table->index('source')->comment('Index for source filtering');
        });

        // Índices en tabla media para búsquedas de relación
        Schema::table('media', function (Blueprint $table) {
            // Índice compuesto para consultas que buscan media de un documento
            $table->index(['model_id', 'model_type'])->comment('Index for media relationships');
        });

        // Índices en tabla de cliente para búsquedas por nombre
        Schema::table('aalv_customer', function (Blueprint $table) {
            // Índices para búsqueda por nombre
            $table->index('firstname')->comment('Index for firstname searches');
            $table->index('lastname')->comment('Index for lastname searches');

            // Índice compuesto para búsquedas combinadas
            $table->index(['firstname', 'lastname'])->comment('Index for combined name searches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['reference']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['upload_at', 'proccess']);
            $table->dropIndex(['source']);
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['model_id', 'model_type']);
        });

        Schema::table('aalv_customer', function (Blueprint $table) {
            $table->dropIndex(['firstname']);
            $table->dropIndex(['lastname']);
            $table->dropIndex(['firstname', 'lastname']);
        });
    }
};