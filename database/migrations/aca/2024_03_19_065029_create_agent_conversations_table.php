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
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('unique_id');
            $table->string('sender_username')->nullable();
            $table->string('reciever_username')->nullable();
            $table->bigInteger('sender_user_id')->unsigned();
            $table->bigInteger('receiver_user_id')->unsigned();
            $table->longText('message');
            $table->string('message_type')->nullable();
            $table->longText('delete_status')->nullable();
            $table->longText('mark_as_unread')->nullable();
            $table->string('message_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_conversations');
    }
};
