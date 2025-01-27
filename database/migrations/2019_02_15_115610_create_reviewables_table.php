<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewablesTable extends Migration
{

    public function up()
    {
        Schema::create('reviewables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->unsigned();
            $table->integer('reviewable_id');
            $table->string('reviewable_type');
            $table->integer('rating')->nullable();
            $table->tinyInteger('approved')->default(1);
            $table->tinyInteger('featured')->default(1);
            $table->text('content')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('reviewables');
    }
}
