<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idiomas que habla cada agente (códigos ISO-639, p. ej. ["es","en"]).
 * Los usa el ruteo por idioma de la auto-asignación de tickets
 * (AssignmentService + helpdesk_tickets.detected_language). Nullable: un
 * agente sin idiomas configurados no participa como "hablante" pero sigue
 * siendo elegible en el pool completo (fallback).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_agent_settings', function (Blueprint $table) {
            $table->json('languages')->nullable()->after('preferences');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_agent_settings', function (Blueprint $table) {
            $table->dropColumn('languages');
        });
    }
};
