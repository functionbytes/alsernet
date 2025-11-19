<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiveChatCannedsTable extends Migration
{

    public function up()
    {
        Schema::create('live_chat_canneds', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->longText('messages')->nullable();
            $table->tinyInteger('available')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_chat_canneds');
    }
}
