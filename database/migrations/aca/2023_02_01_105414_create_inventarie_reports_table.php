<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarieReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('inventarie_reports', function (Blueprint $table) {
                $table->bigIncrements('id');
               $table->string('slack', 30)->unique();
                $table->unsignedBigInteger('product_id')->unsigned();
                $table->unsignedBigInteger('location_original_id')->unsigned();
                $table->unsignedBigInteger('location_validate_id')->unsigned();
                $table->unsignedBigInteger('condition_id')->unsigned();
                $table->unsignedBigInteger('user_id')->unsigned();
                $table->integer('count');
                $table->foreign('product_id')->references('id')->on('inventarie_products')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('location_original_id')->references('id')->on('inventarie_locations')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('location_validate_id')->references('id')->on('inventarie_locations')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('condition_id')->references('id')->on('inventarie_conditions_reports')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
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
