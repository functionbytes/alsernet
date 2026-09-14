<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Añade el estado 'suppressed' al enum de email_logs.status — mismo patrón
 * que 2026_08_07_100000_add_bounced_status_to_email_logs_table.php (MySQL
 * necesita MODIFY COLUMN crudo, sqlite no lo necesita porque la columna ya
 * es TEXT con un CHECK que no se valida ahí).
 *
 * Un envío queda 'suppressed' cuando EnforceEmailSuppression bloquea el
 * intento porque el destinatario está en la lista de supresión — nunca pasa
 * por queued→sent porque el envío real ni siquiera sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE email_logs MODIFY status ENUM('queued', 'sent', 'failed', 'bounced', 'complained', 'suppressed') NOT NULL DEFAULT 'queued'");
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'suppressed_at')) {
                $table->timestamp('suppressed_at')->nullable()->after('complained_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'suppressed_at')) {
                $table->dropColumn('suppressed_at');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE email_logs MODIFY status ENUM('queued', 'sent', 'failed', 'bounced', 'complained') NOT NULL DEFAULT 'queued'");
        }
    }
};
