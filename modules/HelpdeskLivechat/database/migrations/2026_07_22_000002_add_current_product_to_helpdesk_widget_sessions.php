<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_widget_sessions', function (Blueprint $table) {
            // Producto que el visitante está viendo (id, título, imagen, precio,
            // url). Reportado por el snippet en el heartbeat; permite al agente
            // ver el producto ANTES de abrir el chat (covisualización estilo Oct8ne).
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_widget_sessions', 'current_product')) {
                $table->json('current_product')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_widget_sessions', function (Blueprint $table) {
            $table->dropColumn('current_product');
        });
    }
};
