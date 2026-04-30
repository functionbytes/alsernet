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
        Schema::create('chat_conversation_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('account_id')->constrained('chat_accounts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('chat_customers')->nullOnDelete();
            $table->string('token', 64)->unique('chat_conv_sessions_token_unique');
            $table->string('session_id')->nullable();
            $table->json('session_data')->nullable();
            $table->timestamp('last_activity_at');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['account_id', 'token'], 'chat_conv_sessions_account_token_idx');
            $table->index(['customer_id', 'active'], 'chat_conv_sessions_customer_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_sessions');
    }
};
