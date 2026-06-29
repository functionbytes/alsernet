<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // template: used as a WHERE filter in PageService::getPages()
            $table->index('template');

            // updated_at: used as an ORDER BY option in the allowed sort list
            $table->index('updated_at');

            // deleted_at: used by SoftDeletes scopes on every query
            $table->index('deleted_at');

            // published_at standalone: ORDER BY in SearchController and allowed sort column
            // (composite ['status','published_at'] already exists; this covers ORDER-only queries)
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['template']);
            $table->dropIndex(['updated_at']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['published_at']);
        });
    }
};
