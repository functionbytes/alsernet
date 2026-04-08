<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('ticket_comment_id')->nullable();
            $table->string('direction');
            $table->string('message_id')->nullable();
            $table->string('in_reply_to')->nullable();
            $table->text('references')->nullable();
            $table->string('from');
            $table->string('to');
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->json('attachments')->nullable();
            $table->json('headers')->nullable();
            $table->string('status')->default('pending');
            $table->text('delivery_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->longText('raw_email')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->onDelete('cascade');
            $table->foreign('ticket_comment_id')->references('id')->on('helpdesk_ticket_comments')->onDelete('set null');

            $table->index('ticket_id');
            $table->index('direction');
            $table->index('status');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_mails');
    }
};
