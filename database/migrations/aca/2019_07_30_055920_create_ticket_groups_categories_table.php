<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketGroupsCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('ticket_groups_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('category_id');
            $table->foreign('group_id')->references('id')->on('ticket_groups')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('ticket_categories')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_groups_categories');
    }

}
