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
        Schema::create('chat_conversation_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // null = shared view
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filters')->nullable(); // Store filter configuration as JSON
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false); // Shared with all users
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['account_id', 'user_id']);
            $table->index(['account_id', 'is_shared']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_views');
    }
};
