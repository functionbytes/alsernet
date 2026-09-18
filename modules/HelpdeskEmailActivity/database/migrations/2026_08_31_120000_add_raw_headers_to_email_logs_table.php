<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade `raw_headers`: las cabeceras MIME completas del mensaje tal como se
 * entregaron al destinatario (capturadas en LogEmailQueued, después de
 * stripInternalHeaders() — nunca incluyen las X-Email-Module/X-Entity-*
 * internas). Respeta la misma política de store_body/redacción/truncado que
 * ya aplica a body_html/body_text (ver InspectsMailMessage::headersOf()).
 * Se usa para la traza de envío y para reconstruir la descarga .eml.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'raw_headers')) {
                $table->mediumText('raw_headers')->nullable()->after('body_text');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (Schema::hasColumn('email_logs', 'raw_headers')) {
                $table->dropColumn('raw_headers');
            }
        });
    }
};
