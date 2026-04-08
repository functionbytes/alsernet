<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->longText('body')->nullable();
            $table->longText('html_body')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->json('attachment_urls')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->text('edit_reason')->nullable();
            $table->json('mentioned_user_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ticket_id')->references('id')->on('helpdesk_tickets')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('helpdesk_customers')->onDelete('set null');

            $table->index('ticket_id');
            $table->index('is_internal');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_comments');
    }
};
