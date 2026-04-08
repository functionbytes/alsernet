<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_sla_breaches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('sla_type');
            $table->timestamp('breached_at');
            $table->integer('minutes_over')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->onDelete('cascade');

            $table->index('ticket_id');
            $table->index('breached_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_sla_breaches');
    }
};
