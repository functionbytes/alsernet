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
        Schema::create('social_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('content');
            $table->json('media')->nullable(); // Array of media URLs/paths
            $table->json('variables')->nullable(); // Available variables: {name}, {price}, etc.
            $table->json('hashtags')->nullable(); // Default hashtags
            $table->json('networks')->nullable(); // Which networks this template is for
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
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
        Schema::dropIfExists('social_templates');
    }
};
