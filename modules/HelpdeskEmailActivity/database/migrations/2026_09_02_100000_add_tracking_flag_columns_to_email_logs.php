<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas generadas + índice para los dos flags de seguimiento.
 *
 * El listado calcula las tasas de apertura y clic con ocho COUNT que filtran
 * por `metadata->open_tracking_enabled` / `->click_tracking_enabled`. Al vivir
 * dentro de una columna JSON, ninguno puede usar índice: son recorridos
 * completos. Con 1.519 filas ya suponen el 25 % del tiempo de SQL de la
 * página; con la retención de 90 días por delante, crecen linealmente.
 *
 * Se resuelve con columnas GENERATED … STORED en vez de columnas normales
 * porque así NO hay que migrar los datos existentes ni tocar el código que
 * escribe (LogEmailQueued/LogEmailSent siguen escribiendo solo en `metadata`,
 * que sigue siendo la única fuente de verdad). MariaDB las mantiene al día
 * sola en cada INSERT/UPDATE.
 *
 * La expresión usa CASE + JSON_UNQUOTE y no `IS TRUE`: MariaDB rechaza este
 * último con "Truncated incorrect BOOLEAN value: 'false'" al evaluar los
 * literales JSON.
 *
 * Ojo al leerlas: son SOLO para filtrar. La fuente sigue siendo `metadata`
 * (ver EmailLog::hasOpenTracking()), y estas columnas no se escriben nunca a
 * mano — de ahí que no estén en $fillable.
 */
return new class extends Migration
{
    private const COLUMNAS = ['open_tracking_enabled', 'click_tracking_enabled'];

    public function up(): void
    {
        foreach (self::COLUMNAS as $columna) {
            if (Schema::hasColumn('email_logs', $columna)) {
                continue;
            }

            DB::statement(
                "ALTER TABLE email_logs ADD COLUMN `{$columna}` TINYINT(1) "
                ."GENERATED ALWAYS AS (CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.{$columna}')) = 'true' THEN 1 ELSE 0 END) STORED"
            );
            DB::statement("ALTER TABLE email_logs ADD INDEX `email_logs_{$columna}_index` (`{$columna}`)");
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNAS as $columna) {
            if (! Schema::hasColumn('email_logs', $columna)) {
                continue;
            }

            DB::statement("ALTER TABLE email_logs DROP INDEX `email_logs_{$columna}_index`");
            DB::statement("ALTER TABLE email_logs DROP COLUMN `{$columna}`");
        }
    }
};
