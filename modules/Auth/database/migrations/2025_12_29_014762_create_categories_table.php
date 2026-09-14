<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('title');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->integer('position')->default(0);
            $table->integer('depth')->default(0);
            $table->string('slug')->nullable()->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->enum('visibility', ['public', 'private', 'hidden'])->default('public');
            $table->integer('prestashop_id')->nullable()->unique();
            $table->timestamp('last_synced_at')->nullable();
            $table->enum('sync_direction', ['laravel_to_ps', 'ps_to_laravel', 'bidirectional'])->default('bidirectional');
            $table->timestamps();

            $table->index('active');
            $table->index('parent_id');
            $table->index('prestashop_id');
            $table->index(['parent_id', 'position'], 'hierarchy_position_idx');
            $table->index(['active', 'visibility'], 'state_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
