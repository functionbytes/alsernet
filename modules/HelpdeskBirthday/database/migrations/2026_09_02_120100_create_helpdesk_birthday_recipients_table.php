<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destinatarios de la campaña del día, cada uno con su hora de envío.
 *
 * `scheduled_at` es el corazón del escalonado: en vez de despachar N jobs con
 * ->delay() (que llenaría Redis, impediría pausar de verdad y no sobreviviría a
 * un reinicio), el reparto vive en la BD y un comando por minuto recoge solo lo
 * vencido. Pausar la campaña es entonces un UPDATE de una fila.
 *
 * UNIQUE(campaign_id, email) evita que un cliente duplicado en el ERP (misma
 * dirección en dos fichas) reciba el correo dos veces el mismo día.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_birthday_recipients')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_birthday_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained('helpdesk_birthday_campaigns')
                ->cascadeOnDelete();

            $table->string('erp_customer_id')->nullable();
            $table->string('email');
            $table->string('name')->nullable();
            $table->date('birth_date')->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending|sending|sent|failed|skipped
            $table->string('skip_reason', 40)->nullable();

            // Enlace al log de correo global (email_logs) — ahí viven estado de
            // entrega, rebotes, aperturas y clics, así que no duplicamos nada.
            $table->unsignedBigInteger('email_log_id')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'email']);
            // Nombre explícito: el autogenerado supera el límite de 64
            // caracteres de MySQL para identificadores.
            $table->index(['campaign_id', 'status', 'scheduled_at'], 'helpdesk_birthday_recipients_campaign_status_at_index');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_birthday_recipients');
    }
};
