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
        Schema::create('chat_helpcenter_category_article', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('article_id');
            $table->integer('position')->default(0);
            $table->timestamps();

            // Indexes
            $table->unique(['category_id', 'article_id'], 'hc_cat_art_unique');
            $table->index(['category_id', 'position'], 'hc_cat_art_cat_pos_idx');
            $table->index('article_id', 'hc_cat_art_article_idx');

            // Foreign keys
            $table->foreign('category_id', 'chat_helpcenter_category_article_category_id_foreign')
                ->references('id')
                ->on('chat_helpcenter_categories')
                ->onDelete('cascade');

            $table->foreign('article_id', 'chat_helpcenter_category_article_article_id_foreign')
                ->references('id')
                ->on('chat_helpcenter_articles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_helpcenter_category_article');
    }
};
