<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_glossary_terms', function (Blueprint $table) {
            $table->id();
            $table->string('source_term', 255);
            $table->string('source_locale', 5)->default('es');
            $table->string('target_term', 255);
            $table->string('target_locale', 5);
            $table->boolean('do_not_translate')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->unique(['source_term', 'source_locale', 'target_locale'], 'blog_glossary_terms_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_glossary_terms');
    }
};
