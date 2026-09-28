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
            // Token firmado por la tienda (alsernetbridge) que permite al
            // Helpdesk editar la cesta de INVITADO de esta sesión vía API. Va
            // aparte del snapshot: nunca se emite al panel ni por broadcast.
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_widget_sessions', 'cart_token')) {
                $table->text('cart_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_widget_sessions', function (Blueprint $table) {
            $table->dropColumn('cart_token');
        });
    }
};
