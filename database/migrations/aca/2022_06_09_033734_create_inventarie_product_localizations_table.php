<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarieProductLocalizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventarie_product_localizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->unsigned();
            $table->unsignedBigInteger('location_id')->unsigned();
            $table->unsignedBigInteger('shop_id')->unsigned();
            $table->integer('count');
            $table->foreign('product_id')->references('id')->on('inventarie_products')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('inventarie_locations')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shops')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('inventarie_product_localizations');
    }
}
