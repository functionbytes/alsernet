<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El bono deja de ser uno para toda la campaña y pasa a ser de cada
 * destinatario.
 *
 * Gestión genera un bono por cliente (POST /api-gestion/generacion-bono/, una
 * línea por idcliente), así que cada persona recibe SU código con su propio
 * código de verificación. Un único `coupon_code` en la campaña no podía
 * representar eso: o se repartía el mismo a todos —y el primero que lo gastara
 * lo dejaba inservible para el resto— o no se enviaba nada.
 *
 * Se guarda también el error de generación por fila: si a alguien no se le pudo
 * crear el bono, hay que poder verlo y reintentarlo sin volver a generar los de
 * los demás.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_birthday_recipients')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_recipients', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_birthday_recipients', 'coupon_code')) {
                // El código que ve el cliente y el de verificación que lo
                // acompaña: el segundo hace falta para consultarlo o consumirlo
                // en Gestión, y sin él el bono no se puede usar.
                $table->string('coupon_code', 60)->nullable()->after('birth_date');
                $table->string('coupon_verification_code', 20)->nullable()->after('coupon_code');
                $table->timestamp('coupon_generated_at')->nullable()->after('coupon_verification_code');
                $table->string('coupon_error')->nullable()->after('coupon_generated_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_birthday_recipients', 'coupon_code')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_birthday_recipients', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'coupon_verification_code', 'coupon_generated_at', 'coupon_error']);
        });
    }
};
