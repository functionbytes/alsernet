<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_csat_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chat_accounts')->onDelete('cascade');
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('chat_customers')->onDelete('cascade');
            $table->integer('rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('conversation_id');
            $table->index('rating');
            $table->index('responded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_csat_surveys');
    }
};
