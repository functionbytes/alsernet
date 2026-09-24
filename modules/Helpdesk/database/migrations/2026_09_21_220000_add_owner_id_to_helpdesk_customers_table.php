<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Responsable del contacto (Contactos 360: "Asignar agente responsable" de
     * Acciones masivas y "Comercial asignado" de la ficha). Sin foreign key a
     * propósito: `users` vive en otra conexión que `helpdesk_customers`, y una
     * FK entre bases distintas no es portable. La integridad la cubre la
     * validación (solo agentes del catálogo) y la relación Customer::owner().
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'owner_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->after('is_vip');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'owner_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropIndex(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};
