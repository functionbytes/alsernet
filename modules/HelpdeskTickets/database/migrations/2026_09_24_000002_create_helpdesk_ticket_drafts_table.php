<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Borradores del composer guardados en servidor (24-sep-2026): sobreviven a
 * cambiar de equipo/navegador y permiten avisar "X tiene un borrador sin
 * enviar" a quien abre el mismo ticket. Uno por agente y ticket.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_drafts')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_ticket_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body');
            $table->string('mode', 5)->default('reply');
            $table->timestamps();

            $table->unique(['ticket_id', 'user_id']);
            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_drafts');
    }
};
