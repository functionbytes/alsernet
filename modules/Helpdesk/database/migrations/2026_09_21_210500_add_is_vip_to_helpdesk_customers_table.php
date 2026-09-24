<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Columna dedicada (no CustomAttribute): la vista guardada "VIP" del
     * listado de Contactos filtra por este flag en cada carga paginada, y el
     * sistema de custom attributes de Customer apunta a una pivote
     * (helpdesk_attributables) que no tiene migración en este repo — un WHERE
     * directo sobre una columna indexada es más simple y no depende de ella.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'is_vip')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->boolean('is_vip')->default(false)->after('ban_reason');
            $table->index('is_vip');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'is_vip')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropIndex(['is_vip']);
            $table->dropColumn('is_vip');
        });
    }
};
