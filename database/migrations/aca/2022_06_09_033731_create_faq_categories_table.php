<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaqCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->longText('slug')->nullable();
            $table->tinyInteger('available')->default(0);
            $table->timestamps();
        });

    }


    public function down()
    {
        Schema::dropIfExists('faq_categories');
    }
}
