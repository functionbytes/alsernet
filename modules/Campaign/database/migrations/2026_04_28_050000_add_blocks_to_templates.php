<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email Builder: añade columna `blocks` JSON a campaign_templates.
 *
 * El builder visual guarda el árbol de bloques estructurado aquí. Al guardar,
 * el renderer lo compila a HTML y lo persiste en la columna `html` (cache
 * para envío). `blocks` es la fuente de verdad editable.
 *
 *   blocks = [
 *     {"type": "header", "settings": {...}, "content": {...}},
 *     {"type": "hero", "settings": {...}, "content": {...}},
 *     ...
 *   ]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_templates', function (Blueprint $table): void {
            $table->json('blocks')->nullable()->after('content');
            $table->json('global_settings')->nullable()->after('blocks');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_templates', function (Blueprint $table): void {
            $table->dropColumn(['blocks', 'global_settings']);
        });
    }
};
