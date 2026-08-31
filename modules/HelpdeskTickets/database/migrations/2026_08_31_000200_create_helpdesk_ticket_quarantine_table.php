<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correos entrantes retenidos por el clasificador de spam.
 *
 * Cuarentena y NO descarte, deliberadamente. La lista negra existente bloquea
 * por remitente conocido: un acierto ahí es seguro. Un clasificador, en cambio,
 * se equivoca, y su falso positivo es un cliente real cuyo problema desaparece
 * en silencio — nadie recibe un error, nadie sabe que existió. Con cuarentena,
 * el peor caso es un correo que tarda en atenderse; sin ella, es un cliente
 * perdido sin rastro.
 *
 * Se guarda el correo entero para poder reconstruir el ticket al liberarlo.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_quarantine')) {
            return;
        }

        $schema->create('helpdesk_ticket_quarantine', function (Blueprint $table) {
            $table->id();

            $table->string('from_email')->index();
            $table->string('from_name')->nullable();
            $table->string('subject', 500)->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('message_id', 998)->nullable();

            // Por qué se retuvo, para poder auditar (y afinar) el clasificador.
            $table->decimal('spam_score', 4, 3)->default(0);
            $table->string('reason', 500)->nullable();

            // pending | released | confirmed. `released` guarda el ticket que
            // se acabó creando, para poder medir los falsos positivos.
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedBigInteger('released_ticket_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at'], 'hd_quarantine_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_quarantine');
    }
};
