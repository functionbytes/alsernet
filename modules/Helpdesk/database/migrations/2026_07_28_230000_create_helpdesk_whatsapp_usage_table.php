<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger ligero de envíos salientes de WhatsApp (observabilidad de gasto).
 * Una fila por envío real confirmado a la API de Meta — insert directo, sin
 * updates (no updated_at). `category` distingue las conversaciones que Meta
 * factura (marketing/utility/authentication, abiertas por una plantilla HSM)
 * de las gratuitas (service, respuesta del agente dentro de la ventana de
 * 24h). Solo cubre plantillas HSM y respuestas de texto — los envíos de
 * adjuntos/carrusel no se instrumentan en este MVP.
 *
 * Lo alimenta ConversationsController::sendHsm() y
 * OutboundMessageService::sendReply() (canal whatsapp).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_whatsapp_usage')) {
            return;
        }

        $schema->create('helpdesk_whatsapp_usage', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';

            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('template_name', 128)->nullable();
            // marketing | utility | authentication | service
            $table->string('category', 16)->nullable();
            // template | text
            $table->string('message_type', 16);
            $table->boolean('success')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['category', 'created_at']);
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_whatsapp_usage');
    }
};
