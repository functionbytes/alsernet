<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarieReportsLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

            Schema::create('inventarie_reports_locations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('slack');
                $table->tinyInteger('available')->default(0);
                $table->unsignedBigInteger('location_id')->unsigned();
                $table->foreign('location_id')->references('id')->on('locations')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('inventarie_reports');
    }
}
