<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('message_type'); // incoming, outgoing, activity
            $table->text('content')->nullable();
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->boolean('private')->default(false);
            $table->string('content_type')->default('text'); // text, input_email, input_select, cards, form, input_csat
            $table->json('content_attributes')->nullable();
            $table->json('attachments')->nullable();
            $table->string('source_id')->nullable();
            $table->string('external_source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id', 'hd_messages_conversation_idx');
            $table->index(['sender_type', 'sender_id'], 'hd_messages_sender_idx');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
