<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page Templates / BuilderJS: añade el contrato de almacenamiento del motor
 * BuilderJS portado de acellemail, EN PARALELO al builder Alpine existente.
 *
 * - `json`   : estado serializado de BuilderJS (builder.getData()). Fuente de verdad editable del PageTemplate.
 * - `theme`  : tema del builder (carpeta en resources/themes). Default 'default'.
 * - `source` : 'builder' (editable con BuilderJS) | 'uploaded' (custom HTML, one-way).
 *
 * El HTML renderizado (builder.getHtml()) se guarda en la columna `content` ya existente.
 * El builder Alpine de email sigue usando `blocks`/`global_settings`; ambos conviven aislados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_templates', function (Blueprint $table): void {
            $table->longText('json')->nullable()->after('content');
            $table->string('theme')->default('default')->after('json');
            $table->string('source')->default('builder')->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_templates', function (Blueprint $table): void {
            $table->dropColumn(['json', 'theme', 'source']);
        });
    }
};
