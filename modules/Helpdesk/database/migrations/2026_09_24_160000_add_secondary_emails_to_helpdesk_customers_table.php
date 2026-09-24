<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Emails secundarios del contacto (Contactos 360 · fusión: el email del
     * contacto absorbido se conserva aquí cuando no coincide con el principal).
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'secondary_emails')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->json('secondary_emails')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'secondary_emails')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropColumn('secondary_emails');
        });
    }
};
