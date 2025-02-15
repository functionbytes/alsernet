<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('subscribers_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('category_id')->unsigned();
                $table->unsignedBigInteger('subscribers_id')->unsigned();
                $table->foreign('category_id')->references('id')->on('categories')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('subscribers_id')->references('id')->on('newsletters')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('subscribers_categories');
    }
}
