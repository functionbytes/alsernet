<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extensión "orderlink": qué pedidos de PrestaShop se trataron en qué
 * conversación del helpdesk. Se registra al abrir el pedido en el workspace
 * desde una conversación, al insertar su tarjeta/seguimiento en el chat y al
 * hacer una acción de escritura sobre él. El mapeo de estados (opsmap) usa
 * este vínculo para tocar la conversación correcta en vez de la más reciente
 * del cliente.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ps_order_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            // Cliente del helpdesk en el momento del vínculo (informativo: el
            // mapeo de estados compara con el customer_id actual de la
            // conversación, que es el que sobrevive a una fusión de clientes).
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('ps_order_id');
            $table->string('ps_order_reference', 64)->nullable();
            // opened | card_sent | action — se guarda la más fuerte vista.
            $table->string('source', 20)->default('opened');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'ps_order_id'], 'hd_ps_order_links_conv_order_unique');
            $table->index(['ps_order_id', 'updated_at'], 'hd_ps_order_links_order_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ps_order_links');
    }
};
