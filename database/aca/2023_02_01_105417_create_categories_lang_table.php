<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesLangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('categories_lang', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->string('description');
                $table->unsignedBigInteger('categorie_id')->unsigned();
                $table->unsignedBigInteger('lang_id')->unsigned();
                $table->foreign('categorie_id')->references('id')->on('categories')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('lang_id')->references('id')->on('langs')->onUpdate('cascade')->onDelete('cascade');
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories_lang');
    }
}
