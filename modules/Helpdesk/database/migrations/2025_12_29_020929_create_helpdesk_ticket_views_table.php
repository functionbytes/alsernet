<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->onDelete('cascade');

            $table->unique(['ticket_id', 'user_id'], 'idx_6530');
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_views');
    }
};
