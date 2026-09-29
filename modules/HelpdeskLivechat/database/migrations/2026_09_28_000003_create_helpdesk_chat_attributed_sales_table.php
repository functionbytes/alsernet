<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        // Cesta de la sesión del widget, indexada: al llegar un pedido se busca
        // la conversación por su id_cart (sin recorrer el JSON del snapshot).
        if (! $schema->hasColumn('helpdesk_widget_sessions', 'cart_id')) {
            $schema->table('helpdesk_widget_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('cart_id')->nullable()->index();
            });

        }

        // Ventas atribuidas al chat (último contacto, 30 días — mismo modelo
        // que Oct8ne para poder comparar en el piloto).
        if (! $schema->hasTable('helpdesk_chat_attributed_sales')) {
            $schema->create('helpdesk_chat_attributed_sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('order_reference', 32)->nullable();
                $table->unsignedBigInteger('cart_id')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->string('currency', 8)->nullable();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('agent_id')->nullable()->index();
                $table->boolean('via_bot')->default(false);
                $table->boolean('same_session')->default(false);
                // cart = la cesta del pedido es la que el widget vio en el chat;
                // cookie = hd_chat_session del navegador del cliente.
                $table->string('matched_by', 16);
                $table->timestamp('chat_touched_at')->nullable();
                $table->timestamp('ordered_at')->useCurrent()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('helpdesk_chat_attributed_sales');
        if ($schema->hasColumn('helpdesk_widget_sessions', 'cart_id')) {
            $schema->table('helpdesk_widget_sessions', function (Blueprint $table) {
                $table->dropIndex(['cart_id']);
                $table->dropColumn('cart_id');
            });
        }
    }
};
