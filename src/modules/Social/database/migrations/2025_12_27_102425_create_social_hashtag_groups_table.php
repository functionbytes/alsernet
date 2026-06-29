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
        Schema::create('social_hashtag_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable(); // Travel, Food, Business, etc.
            $table->text('hashtags'); // Comma-separated hashtags
            $table->string('color', 7)->default('#6C757D');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('account_id');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_hashtag_groups');
    }
};
