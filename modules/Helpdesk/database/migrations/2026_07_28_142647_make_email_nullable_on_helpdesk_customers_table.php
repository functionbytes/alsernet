<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * email pasa a nullable: el modal "Nueva conversación" del inbox crea
     * contactos solo con teléfono (WhatsApp) sin pedir correo.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->string('email')->change();
        });
    }
};
