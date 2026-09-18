<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda lo que Gestión responde sobre el bono de cada destinatario
 * (GET /api-gestion/bono/{id}/): importe, compra mínima, fechas de validez,
 * estado y tipo.
 *
 * Se guarda aunque el importe y la validez también estén en la campaña, porque
 * ahí son lo que se pidió y aquí es lo que Gestión concedió de verdad — y
 * cuando un cliente pregunta "¿cuánto era mi bono?", la respuesta buena es la
 * segunda.
 *
 * `coupon_data` conserva la respuesta entera: el ciclo del bono (consultar,
 * consumir, anular) devuelve campos que hoy no se pintan pero que sirven para
 * reconstruir qué pasó sin volver a preguntar al ERP.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_birthday_recipients')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('helpdesk_birthday_recipients', 'coupon_data')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_recipients', function (Blueprint $table) {
            $table->decimal('coupon_amount', 10, 4)->nullable()->after('coupon_verification_code');
            $table->decimal('coupon_min_purchase', 10, 2)->nullable()->after('coupon_amount');
            $table->date('coupon_valid_from')->nullable()->after('coupon_min_purchase');
            $table->date('coupon_valid_to')->nullable()->after('coupon_valid_from');
            // Estado tal como lo llama Gestión ("activo", "caducado",
            // "consumido"): se guarda el texto además del código porque el
            // catálogo de estados es suyo y puede crecer.
            $table->string('coupon_status', 40)->nullable()->after('coupon_valid_to');
            $table->json('coupon_data')->nullable()->after('coupon_status');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_birthday_recipients', 'coupon_data')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_recipients', function (Blueprint $table) {
            $table->dropColumn([
                'coupon_amount', 'coupon_min_purchase', 'coupon_valid_from',
                'coupon_valid_to', 'coupon_status', 'coupon_data',
            ]);
        });
    }
};
