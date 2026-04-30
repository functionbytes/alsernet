<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('description')->nullable();
            $table->boolean('show_on_sidebar')->default(true);
            $table->timestamps();

            $table->index('account_id');
            $table->unique(['account_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_labels');
    }
};
