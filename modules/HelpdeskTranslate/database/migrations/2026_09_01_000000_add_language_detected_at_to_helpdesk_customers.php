<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Distingue "language = 'es' porque la tabla lo sembró por defecto" de
     * "language = 'es' porque detectViaProvider() lo confirmó de verdad" —
     * sin esta marca, TranslateIncomingMessage volvía a pagar una detección
     * completa en CADA mensaje de cada cliente hispanohablante (el valor por
     * defecto nunca se distinguía de uno ya confirmado). Ver
     * TranslateIncomingMessage::resolveCustomerLanguage().
     */
    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_customers')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'language_detected_at')) {
                $table->timestamp('language_detected_at')->nullable()->after('language');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_customers')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'language_detected_at')) {
                $table->dropColumn('language_detected_at');
            }
        });
    }
};
