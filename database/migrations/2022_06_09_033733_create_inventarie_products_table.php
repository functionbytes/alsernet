<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarieProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventarie_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->string('slug');
            $table->string('reference');
            $table->string('barcode');
            $table->integer('count');
            $table->integer('management');
            $table->integer('kardex');
            $table->tinyInteger('available')->default(0);
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('location_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('location_id')->references('id')->on('inventarie_locations')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('inventarie_products');
    }
}
