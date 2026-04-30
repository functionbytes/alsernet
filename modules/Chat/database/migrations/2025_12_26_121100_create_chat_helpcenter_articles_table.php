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
        Schema::create('chat_helpcenter_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('position')->default(0);
            $table->longText('body')->nullable();
            $table->text('description')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('draft')->default(true);
            $table->boolean('hide_from_structure')->default(false);
            $table->integer('views')->default(0);
            $table->integer('was_helpful')->default(0);
            $table->unsignedBigInteger('author_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('author_id', 'hc_art_author_idx');
            $table->index(['draft', 'created_at'], 'hc_art_draft_created_idx');
            $table->index('position', 'hc_art_position_idx');
            $table->index('views', 'hc_art_views_idx');
            $table->index('draft');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_helpcenter_articles');
    }
};
