<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Respuestas programadas (send later): el agente redacta una respuesta y elige
 * enviarla a una hora futura. Un comando programado la envía al vencer. Paridad
 * con Front/Gmail. Puede cancelarse mientras siga pendiente.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_scheduled_replies')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_ticket_scheduled_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamp('deliver_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['deliver_at', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_scheduled_replies');
    }
};
