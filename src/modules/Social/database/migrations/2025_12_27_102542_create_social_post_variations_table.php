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
        Schema::create('social_post_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->string('network'); // facebook, instagram, linkedin, etc.
            $table->text('content');
            $table->json('media')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('settings')->nullable(); // Network-specific settings
            $table->timestamps();

            $table->index(['post_id', 'network']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_post_variations');
    }
};
