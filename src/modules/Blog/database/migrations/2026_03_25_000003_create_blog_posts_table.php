<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('description', 500)->nullable();
            $table->longText('content')->nullable();
            $table->enum('status', ['draft', 'published', 'pending'])->default('draft');
            $table->tinyInteger('is_featured')->unsigned()->default(0);
            $table->string('image', 255)->nullable();
            $table->string('format_type', 30)->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index(['status', 'published_at']);
            $table->index('user_id');
            $table->index('is_featured');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
