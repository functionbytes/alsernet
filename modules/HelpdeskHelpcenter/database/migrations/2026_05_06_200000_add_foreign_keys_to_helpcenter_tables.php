<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_helpcenter_article_translations', function (Blueprint $table) {
            $table->foreign('article_id', 'fk_hc_translations_article')
                ->references('id')->on('helpdesk_helpcenter_articles')
                ->cascadeOnDelete();
        });

        Schema::connection($this->connection)->table('helpdesk_helpcenter_article_votes', function (Blueprint $table) {
            $table->foreign('article_id', 'fk_hc_votes_article')
                ->references('id')->on('helpdesk_helpcenter_articles')
                ->cascadeOnDelete();
        });

        Schema::connection($this->connection)->table('helpdesk_helpcenter_article_embeddings', function (Blueprint $table) {
            $table->foreign('article_id', 'fk_hc_embeddings_article')
                ->references('id')->on('helpdesk_helpcenter_articles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_helpcenter_article_translations', function (Blueprint $table) {
            $table->dropForeign('fk_hc_translations_article');
        });

        Schema::connection($this->connection)->table('helpdesk_helpcenter_article_votes', function (Blueprint $table) {
            $table->dropForeign('fk_hc_votes_article');
        });

        Schema::connection($this->connection)->table('helpdesk_helpcenter_article_embeddings', function (Blueprint $table) {
            $table->dropForeign('fk_hc_embeddings_article');
        });
    }
};
