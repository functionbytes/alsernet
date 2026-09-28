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
            // Cesta en vivo del visitante (invitado o cliente) tal como la
            // devuelve la tienda: id, total, nº de productos y líneas.
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_widget_sessions', 'cart_snapshot')) {
                $table->json('cart_snapshot')->nullable();
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_widget_sessions', 'cart_updated_at')) {
                $table->timestamp('cart_updated_at')->nullable();
            }
            // Últimos productos vistos (máx. 20), guardados por el widget.
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_widget_sessions', 'viewed_products')) {
                $table->json('viewed_products')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_widget_sessions', function (Blueprint $table) {
            $table->dropColumn(['cart_snapshot', 'cart_updated_at', 'viewed_products']);
        });
    }
};
