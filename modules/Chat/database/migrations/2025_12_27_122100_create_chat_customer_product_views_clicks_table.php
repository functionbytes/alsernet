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
        Schema::create('chat_customer_product_views_clicks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id')->index('hd_cust_pvclicks_session_idx');
            $table->unsignedBigInteger('conversation_id')->nullable()->index('hd_cust_pvclicks_conv_idx');
            $table->integer('product_id')->index('hd_cust_pvclicks_product_idx');
            $table->string('url', 512);
            $table->timestamps();

            // Foreign keys
            $table->foreign('session_id')->references('id')->on('chat_conversation_sessions')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('chat_conversations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_customer_product_views_clicks');
    }
};
