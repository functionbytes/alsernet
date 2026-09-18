<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idioma en el que se le escribió a cada destinatario.
 *
 * Se guarda el ISO resuelto (no el id de idioma del ERP) para que el histórico
 * siga siendo legible aunque cambie el mapeo o se reordenen los idiomas del
 * módulo Mailer.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_birthday_recipients', 'lang')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_recipients', function (Blueprint $table) {
            $table->string('lang', 5)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_birthday_recipients', function (Blueprint $table) {
            $table->dropColumn('lang');
        });
    }
};
