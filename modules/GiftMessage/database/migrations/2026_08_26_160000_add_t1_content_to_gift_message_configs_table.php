<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            // Que se imprime en el texto grande de cada pieza: el mensaje regalo
            // o el nombre de quien lo recibe. El sobre lleva el nombre (es lo
            // que se lee al repartir) y la tarjeta el mensaje.
            $table->string('env_t1_content', 20)->default('recipient')->after('max_message_length');
            $table->string('card_t1_content', 20)->default('message')->after('env_t1_content');
        });
    }

    public function down(): void
    {
        Schema::table('gift_message_configs', function (Blueprint $table) {
            $table->dropColumn(['env_t1_content', 'card_t1_content']);
        });
    }
};
