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
        Schema::create('seo_static_urls', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->decimal('priority', 2, 1)->default(0.5);
            $table->enum('changefreq', ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])->default('weekly');
            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_static_urls');
    }
};
