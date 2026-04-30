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
        Schema::create('chat_conversation_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_item_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->useCurrent();

            // Indexes
            $table->index('conversation_item_id');
            $table->index('user_id');
            $table->index('read_at');
            $table->unique(['conversation_item_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_reads');
    }
};
