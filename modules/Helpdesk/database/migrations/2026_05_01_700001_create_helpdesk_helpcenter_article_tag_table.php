<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasTable('helpdesk_helpcenter_article_tag')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_helpcenter_article_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('helpdesk_helpcenter_articles')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('helpdesk_helpcenter_tags')->onDelete('cascade');

            $table->unique(['article_id', 'tag_id']);
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_helpcenter_article_tag');
    }
};
