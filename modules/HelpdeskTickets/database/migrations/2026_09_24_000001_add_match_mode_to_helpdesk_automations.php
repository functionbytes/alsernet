<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas de tickets con "se cumple CUALQUIERA" además de "se cumplen TODAS"
 * (24-sep-2026). Default 'all' = comportamiento de siempre. La tabla la
 * comparte el motor de Conversaciones, que simplemente ignora la columna.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_automations', 'match_mode')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_automations', function (Blueprint $table) {
            $table->string('match_mode', 3)->default('all')->after('conditions');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_automations', 'match_mode')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_automations', function (Blueprint $table) {
            $table->dropColumn('match_mode');
        });
    }
};
