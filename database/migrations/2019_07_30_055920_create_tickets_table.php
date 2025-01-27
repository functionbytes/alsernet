<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{

    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cust_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('priority_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('category_id');
            $table->string('ticket_id')->unique()->nullable();
            $table->string('subject');
            $table->string('priority')->nullable();
            $table->longtext('message');
            $table->string('replystatus')->nullable();
            $table->bigInteger('toassignuser_id');
            $table->bigInteger('myassignuser_id');
            $table->datetime('last_reply')->nullable();
            $table->datetime('auto_replystatus')->nullable();
            $table->date('closing_ticket')->nullable();
            $table->date('auto_close_ticket')->nullable();
            $table->string('overduestatus')->nullable();
            $table->date('auto_overdue_ticket')->nullable();
            $table->string('employeesreplying')->nullable();
            $table->string('usernameverify')->nullable();
            $table->string('emailticketfile')->nullable();
            $table->foreign('cust_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('status_id')->references('id')->on('ticket_status')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('priority_id')->references('id')->on('ticket_priorities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('category_id')->references('id')->on('ticket_categories')->onDelete('cascade')->onUpdate('cascade');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }

}
