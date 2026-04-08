<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropColumn('locale');
            $table->unique(['page_id', 'locale_id']);
            $table->index(['locale_id', 'slug']);
            $table->index(['locale_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('page_translations', function (Blueprint $table) {
            $table->dropUnique(['page_id', 'locale_id']);
            $table->dropIndex(['locale_id', 'slug']);
            $table->dropIndex(['locale_id', 'status', 'published_at']);
            $table->string('locale', 10)->nullable()->after('page_id');
        });
    }
};
