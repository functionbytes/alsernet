<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice compuesto (status, created_at) para el listado filtrado por estado.
 *
 * El listado ordena SIEMPRE por created_at DESC (ver
 * EmailLogController::buildListData()). Con el índice suelto de `status`,
 * filtrar por estado resolvía el WHERE por índice pero ordenaba con
 * filesort:
 *
 *   EXPLAIN … WHERE status='bounced' ORDER BY created_at DESC
 *   → type=ref  key=email_logs_status_index  Extra=…; Using filesort
 *
 * Con el compuesto, el propio índice ya entrega las filas en el orden final
 * y el filesort desaparece. A las pocas miles de filas de hoy da igual; con
 * un histórico grande (retention_days permite 90+ días de envíos) es la
 * diferencia entre leer 15 filas y ordenar todas las de ese estado.
 *
 * El índice suelto de `status` se conserva: lo siguen usando los COUNT por
 * estado de las tarjetas de KPI, que no ordenan por fecha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'email_logs_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex('email_logs_status_created_at_index');
        });
    }
};
