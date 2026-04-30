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
        Schema::create('chat_conversation_product_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('chat_customer_sessions')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('chat_conversations')->nullOnDelete();
            $table->integer('product_id');
            $table->string('url', 512);
            $table->timestamps();

            $table->index('session_id', 'hd_pclicks_session_idx');
            $table->index('conversation_id', 'hd_pclicks_conv_idx');
            $table->index('product_id', 'hd_pclicks_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_product_clicks');
    }
};
