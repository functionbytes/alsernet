<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue de dónde vino cada fila de email_log_opens: 'pixel' (el gif de
 * 1x1 servido por EmailOpenTrackingController, único origen hasta ahora) o
 * 'provider' (evento 'open' de un webhook de proveedor — SES/Mailgun/
 * Postmark/Mailrelay ya notifican esto, pero EmailProviderWebhookController
 * hoy solo procesa bounce/complaint; ingerir aperturas de proveedor es un
 * paso posterior). Se van a ingerir AMBAS fuentes a la vez, así que esto NO
 * es una migración de un valor a otro: es una columna nueva desde ya.
 *
 * string(20) en vez de ENUM nativo de MySQL — mismo criterio que
 * email_suppressions.reason (ver 2026_08_31_130000_create_email_suppressions_table):
 * evita el ALTER MODIFY crudo que sí hace falta para email_logs.status cada
 * vez que se añade un valor nuevo.
 *
 * default('pixel') porque las filas existentes solo pudieron venir del pixel
 * (es el único origen que ha existido hasta ahora).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_log_opens')) {
            return;
        }

        Schema::table('email_log_opens', function (Blueprint $table) {
            if (! Schema::hasColumn('email_log_opens', 'source')) {
                // 'pixel'|'provider' — ver Modules\HelpdeskEmailActivity\Enums\EmailOpenSource
                $table->string('source', 20)->default('pixel')->after('email_log_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_log_opens')) {
            return;
        }

        Schema::table('email_log_opens', function (Blueprint $table) {
            if (Schema::hasColumn('email_log_opens', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
