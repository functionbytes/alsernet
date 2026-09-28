<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        // Piloto chat propio vs Oct8ne: grupo de TODOS los pedidos (no solo
        // los atribuidos al chat) para comparar ingresos por grupo.
        if (! Schema::connection($this->connection)->hasTable('helpdesk_chat_pilot_orders')) {
            Schema::connection($this->connection)->create('helpdesk_chat_pilot_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('bucket', 10); // widget | oct8ne
                $table->unsignedTinyInteger('pilot_percent')->nullable();
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamp('ordered_at')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_chat_pilot_orders');
    }
};
