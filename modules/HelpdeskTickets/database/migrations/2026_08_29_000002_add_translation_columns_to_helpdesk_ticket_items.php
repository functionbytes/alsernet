<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mismo par de columnas que `helpdesk_conversation_items` en HelpdeskTranslate
 * (2026_05_06_180001_.../2026_05_06_300000_...), para que la traducción de
 * mensajes de ticket siga exactamente el mismo mecanismo ya probado en
 * Conversaciones: `translated_body`/`source_locale` para lo que escribe el
 * cliente, `outgoing_translated_body`/`outgoing_target_locale` para lo que
 * escribe el agente. Guards con Schema::hasColumn() — mismo criterio
 * idempotente que usan las migraciones de HelpdeskTranslate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_items', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_items', 'translated_body')) {
                $table->longText('translated_body')->nullable()->after('body');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_items', 'source_locale')) {
                $table->string('source_locale', 8)->nullable()->after('translated_body');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_items', 'outgoing_translated_body')) {
                $table->longText('outgoing_translated_body')->nullable()->after('source_locale');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_items', 'outgoing_target_locale')) {
                $table->string('outgoing_target_locale', 8)->nullable()->after('outgoing_translated_body');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_items', function (Blueprint $table) {
            foreach (['translated_body', 'source_locale', 'outgoing_translated_body', 'outgoing_target_locale'] as $column) {
                if (Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
