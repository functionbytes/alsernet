<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade `attribute_changes` a activity_log.
 *
 * En el proyecto conviven DOS copias de spatie/laravel-activitylog:
 *
 *   - vendor/spatie/laravel-activitylog            → v5
 *   - modules/Activity/vendor/spatie/…             → v4
 *
 * El autoloader resuelve unas clases a una copia y otras a la otra, así que
 * hay dos caminos vivos para registrar actividad:
 *
 *   - El trait LogsActivity (Ticket, Role…) usa el camino v4, que NO escribe
 *     esta columna → funciona con el esquema actual.
 *   - El helper activity()->log() usa el camino v5
 *     (Support\ActivityLogger → Actions\LogActivityAction), que SIEMPRE la
 *     escribe → falla con "Unknown column 'attribute_changes'".
 *
 * Resultado: cada llamada a activity() del proyecto reventaba. En
 * HelpdeskEmailActivity el fallo era además invisible, porque logActivity() lo
 * envuelve en rescue(report: false), y su pestaña "Bitácora" llevaba desde
 * siempre 0 registros. En Role, Mailer, Backup, Document y Database no hay
 * rescue y la excepción sube.
 *
 * La columna se añade nullable y al final de la tabla: la copia v4 la ignora
 * (no la escribe ni la lee) y la v5 pasa a poder insertar, de modo que los
 * dos caminos conviven sin tocar el autoload ni desinstalar ninguna copia
 * — eso es una limpieza de dependencias aparte, y más arriesgada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('activity_log', 'attribute_changes')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            $table->json('attribute_changes')->nullable()->after('properties');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
            return;
        }

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('attribute_changes');
        });
    }
};
