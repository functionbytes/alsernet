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
        Schema::create('chat_conversation_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('inbox_id')->index('messages_inbox_id_foreign');
            $table->unsignedBigInteger('conversation_id');
            $table->string('sender_type');
            $table->unsignedBigInteger('sender_id');
            $table->string('message_type')->default('incoming');
            $table->string('content_type')->default('text');
            $table->text('content');
            $table->string('source_id')->nullable()->index('messages_source_id_index');
            $table->string('status')->default('sent');
            $table->boolean('private')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'message_type'], 'messages_account_id_message_type_index');
            $table->index(['sender_type', 'sender_id'], 'messages_sender_type_sender_id_index');

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('chat_accounts')->onDelete('cascade');
            $table->foreign('inbox_id')->references('id')->on('chat_inboxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_messages');
    }
};
