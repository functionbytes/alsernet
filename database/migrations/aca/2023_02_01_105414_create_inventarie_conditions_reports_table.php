<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarieConditionsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventarie_conditions_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
           $table->string('slack', 30)->unique();
            $table->string('title');
            $table->string('slug');
            $table->tinyInteger('available')->default(0);
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
        Schema::dropIfExists('inventarie_conditions_reports');
    }
}
