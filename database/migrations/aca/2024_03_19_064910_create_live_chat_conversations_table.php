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
        Schema::create('live_chat_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('unique_id');
            $table->bigInteger('livechat_cust_id')->nullable();
            $table->bigInteger('livechat_user_id')->unsigned();
            $table->string('message_type')->nullable();
            $table->string('livechat_username');
            $table->longText('message');
            $table->string('status')->nullable();
            $table->longText('delete')->nullable();
            $table->string('sender_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chat_conversations');
    }
};
