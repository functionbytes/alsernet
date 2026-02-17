<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');

            // Basic SEO
            $table->string('title', 255)->nullable()->comment('Page title for SEO');
            $table->text('description')->nullable()->comment('Meta description for search results');
            $table->text('keywords')->nullable()->comment('Meta keywords (comma separated)');

            // Open Graph
            $table->string('og_title', 255)->nullable()->comment('Open Graph title (Facebook, LinkedIn)');
            $table->text('og_description')->nullable()->comment('Open Graph description');
            $table->string('og_image', 500)->nullable()->comment('Open Graph image URL');
            $table->string('og_type', 50)->default('website')->comment('Open Graph type (website, article, etc.)');

            // Twitter Card
            $table->string('twitter_card', 50)->default('summary')->comment('Twitter card type (summary, summary_large_image)');
            $table->string('twitter_title', 255)->nullable()->comment('Twitter card title');
            $table->text('twitter_description')->nullable()->comment('Twitter card description');
            $table->string('twitter_image', 500)->nullable()->comment('Twitter card image URL');

            // Advanced
            $table->string('canonical_url', 500)->nullable()->comment('Canonical URL for duplicate content');
            $table->string('robots', 100)->default('index,follow')->comment('Robots meta tag directive');

            $table->timestamps();

            // Indexes
            $table->index(['seoable_type', 'seoable_id'], 'seoable_index');
            $table->index('robots');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
