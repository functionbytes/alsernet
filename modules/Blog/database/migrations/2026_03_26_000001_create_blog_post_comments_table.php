<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_post_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('author_name', 100);
            $table->string('author_email', 150);
            $table->text('content');
            $table->enum('status', ['pending', 'approved', 'spam'])->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('blog_post_id')
                ->references('id')->on('blog_posts')
                ->cascadeOnDelete();

            $table->foreign('parent_id')
                ->references('id')->on('blog_post_comments')
                ->nullOnDelete();

            $table->index('blog_post_id');
            $table->index('parent_id');
            $table->index('status');
            $table->index(['blog_post_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_comments');
    }
};
