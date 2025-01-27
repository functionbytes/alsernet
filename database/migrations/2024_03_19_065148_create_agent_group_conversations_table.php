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
        Schema::create('agent_group_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('unique_id');
            $table->string('sender_username')->nullable();
            $table->string('sender_image')->nullable();
            $table->longText('reciever_username')->nullable();
            $table->longText('message');
            $table->string('message_type')->nullable();
            $table->bigInteger('sender_user_id')->unsigned();
            $table->longText('receiver_user_id');
            $table->longText('created_user_id');
            $table->longText('delete_status');
            $table->longText('mark_as_unread');
            $table->string('message_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_group_conversations');
    }
};
