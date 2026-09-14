<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda una copia del cuerpo del correo bloqueado (igual que
 * document_mails.body_html/body_text en el módulo Document) para poder
 * mostrar una "Vista previa" del contenido exacto, no solo remitente/asunto.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_email_blacklist_hits', function (Blueprint $table) {
            $table->longText('body_html')->nullable()->after('subject');
            $table->longText('body_text')->nullable()->after('body_html');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_email_blacklist_hits', function (Blueprint $table) {
            $table->dropColumn(['body_html', 'body_text']);
        });
    }
};
