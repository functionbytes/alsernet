<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Side conversations de tickets: hilos laterales privados (con un compañero de
 * equipo o un contacto externo por email) dentro de un ticket, invisibles para
 * el cliente. Paridad con Zendesk/Front. Nativo de tickets (ticket_id) para no
 * acoplar al SideConversation del core, que cuelga de una conversación y muchos
 * tickets no la tienen.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_side_conversations')) {
            Schema::connection($this->connection)->create('helpdesk_ticket_side_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->string('subject');
                $table->enum('participant_type', ['team', 'external_email']);
                $table->string('participant_email')->nullable();
                $table->unsignedBigInteger('participant_user_id')->nullable();
                $table->enum('status', ['open', 'closed'])->default('open')->index();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->index(['ticket_id', 'status']);
            });
        }

        if (! Schema::connection($this->connection)->hasTable('helpdesk_ticket_side_conversation_messages')) {
            Schema::connection($this->connection)->create('helpdesk_ticket_side_conversation_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('side_conversation_id')->index('hd_ticket_side_conv_msgs_side_conv_id_idx');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('from_email')->nullable();
                $table->enum('direction', ['outbound', 'inbound'])->default('outbound');
                $table->text('body');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_side_conversation_messages');
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_side_conversations');
    }
};
