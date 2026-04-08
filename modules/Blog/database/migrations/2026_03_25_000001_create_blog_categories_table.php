<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 255)->unique();
            $table->string('description', 400)->nullable();
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->enum('status', ['published', 'draft'])->default('published');
            $table->string('icon', 60)->nullable();
            $table->integer('order')->default(0);
            $table->tinyInteger('is_featured')->default(0);
            $table->tinyInteger('is_default')->unsigned()->default(0);
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
            $table->index('parent_id');
            $table->index('is_default');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};
