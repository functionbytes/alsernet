<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el desglose de la audiencia del día: cuántos cumplían años y cuántos
 * quedaron fuera por cada motivo (baja en Gestión, sin correo, sin LOPD, sin
 * consentimiento comercial).
 *
 * Hace falta porque esos descartes se resuelven en el WHERE de la consulta: los
 * excluidos nunca llegan a guardarse como destinatarios, así que sin esta
 * columna la campaña solo puede decir "escribí a 576" sin poder explicar qué
 * pasó con los otros 151.
 *
 * En JSON y no en columnas sueltas: la lista de motivos crecerá (idioma sin
 * plantilla, dominio suprimido…) y cada uno nuevo no debería costar una
 * migración.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_birthday_campaigns')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('helpdesk_birthday_campaigns', 'audience_stats')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_campaigns', function (Blueprint $table) {
            $table->json('audience_stats')->nullable()->after('skipped_count');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_birthday_campaigns', 'audience_stats')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_campaigns', function (Blueprint $table) {
            $table->dropColumn('audience_stats');
        });
    }
};
