<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_off_hours_responses', function (Blueprint $table) {
            // null = mensaje generico para el canal (se traduce al vuelo con el
            // proveedor configurado); con idioma = mensaje redactado a mano por
            // un admin para ese idioma exacto, se envia tal cual sin traducir.
            $table->string('language')->nullable()->after('channel')
                ->comment('null = generico (se traduce al vuelo); codigo ISO 639-1 = mensaje redactado para ese idioma');
            $table->unique(['channel', 'language'], 'helpdesk_off_hours_responses_channel_language_unique');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_off_hours_responses', function (Blueprint $table) {
            $table->dropUnique('helpdesk_off_hours_responses_channel_language_unique');
            $table->dropColumn('language');
        });
    }
};
