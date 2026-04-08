<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->unsignedBigInteger('ticket_message_id');
            $table->string('filename', 255);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('path', 500);
            $table->timestamps();

            // Foreign keys
            $table->foreign('ticket_message_id')->references('id')->on('helpdesk_ticket_messages')->onDelete('cascade');

            // Indexes
            $table->index('ticket_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_attachments');
    }
};
