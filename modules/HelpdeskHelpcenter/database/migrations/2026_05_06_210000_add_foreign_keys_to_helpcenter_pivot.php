<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_helpcenter_category_article', function (Blueprint $table) {
            $table->foreign('category_id', 'fk_hc_pivot_category')
                ->references('id')->on('helpdesk_helpcenter_categories')
                ->cascadeOnDelete();

            $table->foreign('article_id', 'fk_hc_pivot_article')
                ->references('id')->on('helpdesk_helpcenter_articles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_helpcenter_category_article', function (Blueprint $table) {
            $table->dropForeign('fk_hc_pivot_category');
            $table->dropForeign('fk_hc_pivot_article');
        });
    }
};
