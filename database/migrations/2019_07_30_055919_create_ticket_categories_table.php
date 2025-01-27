<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->string('slug');
            $table->tinyInteger('available')->default(0);
            $table->unsignedBigInteger('priority_id');
            $table->foreign('priority_id')->references('id')->on('ticket_priorities')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_categories');
    }

}
