<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_internal')->default(false)->index();
            $table->text('message');
            $table->longText('message_html')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_messages');
    }
};
