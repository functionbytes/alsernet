<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketAssignsTable extends Migration
{

    public function up()
    {
        Schema::create('ticket_assigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('toassignuser_id');
            $table->foreign('toassignuser_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onUpdate('cascade')->onDelete('cascade');
        });
    }


    public function down()
    {
        Schema::dropIfExists('ticket_assigns');
    }
}
