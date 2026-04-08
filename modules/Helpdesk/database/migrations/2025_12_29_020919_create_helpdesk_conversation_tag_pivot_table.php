<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_conversation_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('helpdesk_conversations')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('helpdesk_conversation_tags')->onDelete('cascade');

            $table->unique(['conversation_id', 'tag_id'], 'idx_54231');
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_conversation_tag_pivot');
    }
};
