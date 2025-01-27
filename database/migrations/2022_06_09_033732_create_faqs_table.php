<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaqsTable extends Migration
{

    public function up()
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->tinyInteger('available')->default(0);
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('faq_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });

    }


    public function down()
    {
        Schema::dropIfExists('faqs');
    }
}
