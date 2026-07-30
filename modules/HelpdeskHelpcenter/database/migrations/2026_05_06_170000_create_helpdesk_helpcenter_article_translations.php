<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_helpcenter_article_translations')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_helpcenter_article_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->string('locale', 8);
            $table->string('title');
            $table->string('slug')->index();
            $table->longText('body')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'locale'], 'hhat_article_locale_uq');
            $table->index(['locale', 'is_published'], 'hhat_locale_published_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_helpcenter_article_translations');
    }
};
