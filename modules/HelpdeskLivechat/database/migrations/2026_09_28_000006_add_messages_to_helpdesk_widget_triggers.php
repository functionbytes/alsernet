<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        // Traducciones del mensaje proactivo por idioma del visitante
        // ({"en": "...", "fr": "..."}); `message` es el mensaje por defecto.
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_widget_triggers', 'messages')) {
            Schema::connection($this->connection)->table('helpdesk_widget_triggers', function (Blueprint $table) {
                $table->json('messages')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_widget_triggers', function (Blueprint $table) {
            $table->dropColumn('messages');
        });
    }
};
