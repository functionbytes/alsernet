<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketGroupsUsersTable extends Migration
{

    public function up()
    {
        Schema::create('ticket_groups_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('groups_id')->unsigned();
            $table->unsignedBigInteger('users_id')->unsigned();
            $table->foreign('groups_id')->references('id')->on('ticket_groups')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('users_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }


    public function down()
    {
        Schema::dropIfExists('ticket_groups_users');
    }
}
