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
        Schema::create('chat_helpcenter_article_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            // Indexes
            $table->unique(['article_id', 'tag_id'], 'hc_art_tag_unique');
            $table->index('tag_id');

            // Foreign keys
            $table->foreign('article_id', 'chat_helpcenter_article_tag_article_id_foreign')
                ->references('id')
                ->on('chat_helpcenter_articles')
                ->onDelete('cascade');

            $table->foreign('tag_id', 'chat_helpcenter_article_tag_tag_id_foreign')
                ->references('id')
                ->on('chat_helpcenter_tags')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_helpcenter_article_tag');
    }
};
