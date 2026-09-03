<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Resultado de cada comprobación de enlaces de un correo (pestaña "Enlaces" del
 * inspector).
 *
 * Se guarda para poder responder "¿desde cuándo está roto?" — un enlace de un
 * correo enviado hace meses puede haber caído después, y sin histórico cada
 * comprobación borraría la anterior y no habría forma de saber si el fallo es
 * nuevo. Una fila por URL y ejecución: agregarlo por ejecución en un JSON haría
 * imposible consultar "todos los correos que apuntan a esta URL rota".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_link_checks')) {
            return;
        }

        Schema::create('email_link_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained('email_logs')->cascadeOnDelete();
            // Índice sobre un prefijo: una URL puede pasar de los 191 caracteres
            // que caben en un índice de MySQL con utf8mb4.
            $table->text('url');
            $table->string('url_hash', 64)->index();
            // 0 = no se pidió (enlace de seguimiento propio),
            // -1 = no se pudo conectar. Ver EmailLinkCheckService.
            $table->integer('status_code');
            $table->string('status');
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->index();

            $table->index(['email_log_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_link_checks');
    }
};
