<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->boolean('available')->default(1);
            $table->timestamps();

            $table->index('slug');
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('available')->default(1);
            $table->foreignId('category_id')->constrained('faq_categories')->onDelete('cascade');
            $table->timestamps();

            $table->index('slug');
            $table->index('available');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
    }
};
