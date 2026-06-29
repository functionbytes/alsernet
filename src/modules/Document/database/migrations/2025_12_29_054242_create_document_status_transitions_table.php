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
        // Migración duplicada: la tabla document_status_transitions ya la crea
        // 2025_12_29_030512_create_document_status_transitions_table (definición
        // canónica con todas las columnas). Se deja como no-op idempotente para no
        // romper `migrate` en una base de datos fresca con "table already exists".
        if (Schema::hasTable('document_status_transitions')) {
            return;
        }

        Schema::create('document_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: la tabla la gestiona la migración canónica 030512.
    }
};
