<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCommentsTable extends Migration
{

    public function up()
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('cust_id');
            $table->unsignedBigInteger('user_id');
            $table->longtext('comment');
            $table->integer('display')->nullable();
            $table->foreign('ticket_id')->index()->references('id')->on('tickets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('cust_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_comments');
    }
}
