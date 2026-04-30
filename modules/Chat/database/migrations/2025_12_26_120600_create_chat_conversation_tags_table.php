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
        // Only create main table if it doesn't exist
        if (! Schema::hasTable('chat_conversation_tags')) {
            Schema::create('chat_conversation_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('color')->default('#gray');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['account_id', 'is_active']);

                // Foreign key to chat_accounts
                $table->foreign('account_id', 'chat_conversation_tags_account_id_foreign')
                    ->references('id')
                    ->on('chat_accounts')
                    ->onDelete('cascade');
            });
        }

        // Pivot table for conversation <-> tags relationship
        if (! Schema::hasTable('chat_conversation_tag')) {
            Schema::create('chat_conversation_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->foreignId('chat_conversation_tag_id')->constrained('chat_conversation_tags')->cascadeOnDelete();
                $table->timestamp('tagged_at')->useCurrent();

                $table->unique(['conversation_id', 'chat_conversation_tag_id'], 'conversation_tag_unique');
                $table->index('chat_conversation_tag_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_tag');
        Schema::dropIfExists('chat_conversation_tags');
    }
};
