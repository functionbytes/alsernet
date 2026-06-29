<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model_type')->nullable();
            $table->string('title_pattern', 200)->nullable();
            $table->string('description_pattern', 500)->nullable();
            $table->string('og_type', 50)->default('website');
            $table->string('twitter_card', 50)->default('summary_large_image');
            $table->string('robots', 100)->default('index,follow');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['model_type', 'is_active']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_templates');
    }
};
