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
        Schema::create('chat_conversation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type')->default('message');
            $table->longText('body')->nullable();
            $table->longText('html_body')->nullable();
            $table->json('attachment_urls')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('author_id');
            $table->index('user_id');
            $table->index('conversation_id');
            $table->index('type');
            $table->index('is_internal');
            $table->index('created_at');
            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'is_internal']);
            $table->index(['conversation_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_items');
    }
};
