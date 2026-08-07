<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade los estados 'bounced'/'complained' al enum de email_logs.status, para que
 * el procesador de rebotes DSN (Document\Console\Commands\ProcessEmailBouncesCommand)
 * pueda registrar el resultado real de un envío más allá de queued/sent/failed.
 *
 * MySQL no permite ALTER de un ENUM vía Blueprint (no hay helper nativo), así que se
 * usa MODIFY COLUMN crudo. sqlite (usado en tests) no tiene ENUM real — la columna ya
 * es TEXT con un CHECK; se omite ahí porque no aplica (Schema::hasColumn sigue siendo
 * true y CHECK no se valida en sqlite salvo con PRAGMA, así que no hace falta tocarlo).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE email_logs MODIFY status ENUM('queued', 'sent', 'failed', 'bounced', 'complained') NOT NULL DEFAULT 'queued'");
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'bounced_at')) {
                $table->timestamp('bounced_at')->nullable()->after('failed_at')->index();
            }

            if (! Schema::hasColumn('email_logs', 'complained_at')) {
                $table->timestamp('complained_at')->nullable()->after('bounced_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'complained_at')) {
                $table->dropColumn('complained_at');
            }

            if (Schema::hasColumn('email_logs', 'bounced_at')) {
                $table->dropColumn('bounced_at');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE email_logs MODIFY status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued'");
        }
    }
};
