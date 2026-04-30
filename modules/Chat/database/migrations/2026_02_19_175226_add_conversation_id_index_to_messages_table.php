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
        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            // Add composite index for efficient message retrieval after batch insert
            // Used in ProcessFacebookMessageJob and ProcessInstagramMessageJob
            // Query pattern: WHERE conversation_id = ? ORDER BY id DESC LIMIT ?
            $table->index(['conversation_id', 'id'], 'messages_conversation_id_ordered_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_conversation_messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_id_ordered_idx');
        });
    }
};
