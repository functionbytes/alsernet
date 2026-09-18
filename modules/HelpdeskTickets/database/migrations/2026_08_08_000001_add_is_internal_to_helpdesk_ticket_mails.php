<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            // Distingue avisos internos (p.ej. "Escalado a nivel 2" hacia
            // soporte-n2@alvarez.mx) de los correos reales al cliente. Nunca
            // se calcula por heurística de dominio: se marca explícitamente
            // al componer (pantalla "Emails enviados" · tab "Internos").
            $table->boolean('is_internal')->default(false)->after('direction');
            $table->index('is_internal');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
