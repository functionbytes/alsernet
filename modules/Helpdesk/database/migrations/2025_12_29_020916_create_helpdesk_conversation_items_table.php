<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_conversation_items', function (Blueprint $table) {
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

            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('helpdesk_customers')->onDelete('set null');

            $table->index('conversation_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_conversation_items');
    }
};
