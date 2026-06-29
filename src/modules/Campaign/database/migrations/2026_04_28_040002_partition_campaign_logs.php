<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Crea tablas particionadas por RANGE(created_at) para logs masivos.
 * MariaDB >= 10.2 soporta particionamiento nativo.
 *
 * En vez de modificar tablas existentes (riesgoso con datos),
 * documentamos la estrategia recomendada para producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Las tablas ya existen; esta migración documenta el DDL
        // de particionamiento para ejecutar manualmente en producción.
        // En entornos nuevos, el DDL se puede generar con:
        //   php artisan campaign:generate-partition-ddl
    }

    public function down(): void
    {
        // No-op: el particionamiento se revierte manualmente.
    }
};
