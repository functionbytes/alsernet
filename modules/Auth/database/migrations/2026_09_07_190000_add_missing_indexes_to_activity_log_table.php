<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * activity_log tenía SOLO dos índices: la PK y hmac_signature.
 *
 * La migración de creación declara `nullableUlidMorphs('subject')`, `log_name`,
 * `batch_uuid` y `created_at` como indexados, pero en esta base de datos no
 * llegaron a existir (la tabla se creó por otra vía o se perdieron por el
 * camino). Resultado: cualquier lectura del historial de una entidad hace un
 * FULL SCAN de las 28.000 filas — `EXPLAIN` daba `type=ALL, key=NULL,
 * rows=27980`, y el panel de detalle de un ticket gastaba ~70 ms solo en el
 * COUNT + SELECT de su actividad (medido 7-sep-2026).
 *
 * Con la tabla creciendo por cada acción registrada, esto solo empeora.
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    /**
     * @var array<string, array<int, string>>
     */
    private const INDEXES = [
        // El crítico: es el filtro de "actividad de esta entidad".
        'activity_log_subject_index' => ['subject_type', 'subject_id'],
        'activity_log_causer_index' => ['causer_type', 'causer_id'],
        'activity_log_log_name_index' => ['log_name'],
        'activity_log_batch_uuid_index' => ['batch_uuid'],
        'activity_log_created_at_index' => ['created_at'],
    ];

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('activity_log')) {
            return;
        }

        $schema->table('activity_log', function (Blueprint $table) use ($schema) {
            foreach (self::INDEXES as $nombre => $columnas) {
                // hasColumns porque causer_*/batch_uuid pueden no existir según
                // la versión de spatie/activitylog con la que se creó la tabla.
                if ($schema->hasColumns('activity_log', $columnas) && ! $schema->hasIndex('activity_log', $nombre)) {
                    $table->index($columnas, $nombre);
                }
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasTable('activity_log')) {
            return;
        }

        $schema->table('activity_log', function (Blueprint $table) use ($schema) {
            foreach (array_keys(self::INDEXES) as $nombre) {
                if ($schema->hasIndex('activity_log', $nombre)) {
                    $table->dropIndex($nombre);
                }
            }
        });
    }
};
