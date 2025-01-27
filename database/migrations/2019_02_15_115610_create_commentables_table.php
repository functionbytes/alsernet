<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommentablesTable extends Migration {


    public function up()
    {
        Schema::create('commentables', function(Blueprint $table)
        {
            $table->unsignedBigInteger('user_id')->unsigned();
            $table->integer('comment_id');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

        });

    }

    public function down()
    {
        Schema::drop('commentables');
    }

}
