<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_tag_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name', 120);
            $table->string('slug', 255)->nullable();
            $table->string('description', 400)->nullable();
            $table->timestamps();

            $table->unique(['blog_tag_id', 'locale']);
            $table->index(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_tag_translations');
    }
};
