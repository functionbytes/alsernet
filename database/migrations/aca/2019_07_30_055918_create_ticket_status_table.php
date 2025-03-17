<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketStatusTable extends Migration
{

    public function up()
    {
        Schema::create('ticket_status', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->string('slug');
            $table->string('color');
            $table->tinyInteger('available')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_status');
    }

}
