<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasTable('helpdesk_helpcenter_category_article')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_helpcenter_category_article', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('article_id');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'article_id'], 'hh_cat_art_unique');
            $table->index('category_id');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_helpcenter_category_article');
    }
};
