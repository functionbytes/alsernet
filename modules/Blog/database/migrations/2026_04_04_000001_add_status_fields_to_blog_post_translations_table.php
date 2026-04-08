<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_post_translations', function (Blueprint $table) {
            $table->string('status', 20)->default('manual')->after('seo_keywords');
            $table->timestamp('translated_at')->nullable()->after('status');
            $table->string('source_hash', 64)->nullable()->after('translated_at');
        });
    }

    public function down(): void
    {
        Schema::table('blog_post_translations', function (Blueprint $table) {
            $table->dropColumn(['status', 'translated_at', 'source_hash']);
        });
    }
};
