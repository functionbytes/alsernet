<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    public function up(): void {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('messages');
    }
};
