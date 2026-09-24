<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de tareas (checklist) dentro de un ticket (24-sep-2026): pasos
 * internos que el equipo marca al hacerlos. No las ve el cliente.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_ticket_tasks')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_ticket_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('title', 255);
            $table->boolean('is_done')->default(false);
            $table->unsignedBigInteger('done_by')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'position']);
            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_tasks');
    }
};
