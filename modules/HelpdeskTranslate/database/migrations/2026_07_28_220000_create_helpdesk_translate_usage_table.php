<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger ligero de consumo de la API de traducción (observabilidad de cuota).
 * Una fila por llamada real confirmada al proveedor — insert directo, sin
 * updates (no updated_at). A diferencia de helpdesk_translation_cache (que
 * solo registra resultados cacheables y se salta la detección de idioma y
 * las llamadas fallidas), este ledger cuenta TODA llamada real, exitosa o no,
 * para que el reporte de consumo no subestime el gasto de cuota.
 *
 * Lo alimenta CachedTranslator::callProvider()/detectViaProvider() — los
 * únicos puntos donde se confirma que se llamó de verdad al proveedor.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_translate_usage')) {
            return;
        }

        $schema->create('helpdesk_translate_usage', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->id();
            $table->string('provider', 32);
            // translate | detect
            $table->string('operation', 16);
            // manual | auto_incoming | auto_outgoing | other
            $table->string('feature', 32)->default('other');
            $table->unsignedInteger('characters');
            $table->string('source_lang', 8)->nullable();
            $table->string('target_lang', 8)->nullable();
            $table->boolean('success')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['feature', 'created_at']);
            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_translate_usage');
    }
};
