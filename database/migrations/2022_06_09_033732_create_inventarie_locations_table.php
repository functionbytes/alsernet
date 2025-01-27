<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarieLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

            Schema::create('inventarie_locations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('title');
                $table->text('barcode');
                $table->string('latitude')->nullable();
                $table->string('longitude')->nullable();
                $table->tinyInteger('available')->default(0);
                $table->unsignedBigInteger('shop_id')->unsigned();
                $table->foreign('shop_id')->references('id')->on('shops')->onUpdate('cascade')->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventarie_locations');
    }
}
