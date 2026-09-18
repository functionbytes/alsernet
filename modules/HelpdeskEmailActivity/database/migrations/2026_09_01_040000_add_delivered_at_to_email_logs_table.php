<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `sent_at` solo significa "el transporte SMTP lo aceptó" (ver
 * EmailLog::markAsSent(), disparado desde LogEmailSent en el evento
 * MessageSent de Laravel) — nunca confirma que el proveedor de correo
 * realmente lo entregó al buzón del destinatario. `delivered_at` es esa
 * confirmación real, y solo se rellenará cuando llegue un evento 'delivered'
 * de un webhook de proveedor (SES/Mailgun/Postmark/Mailrelay) — fuera del
 * alcance de esta migración, que únicamente prepara la columna.
 *
 * Nullable porque la inmensa mayoría de envíos hoy no tienen ningún
 * proveedor con webhooks de entrega configurado (ver
 * EmailProviderWebhookController, que hoy solo procesa bounce/complaint).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
        });
    }
};
