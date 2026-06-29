<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_translation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_post_id');
            $table->string('locale', 5);
            $table->string('action', 30);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('fields_changed')->nullable();
            $table->string('provider', 30)->nullable()->default('deepl');
            $table->unsignedInteger('characters_used')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('blog_post_id')->references('id')->on('blog_posts')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['blog_post_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_translation_logs');
    }
};
