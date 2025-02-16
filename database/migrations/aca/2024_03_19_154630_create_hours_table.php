<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreateHoursTable extends Migration
{

    public function up()
    {
        Schema::create('hours', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string('no_id')->nullable();
            $table->string('weeks')->nullable();
            $table->string('status')->nullable();
            $table->string('starttime')->nullable();
            $table->string('endtime')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('hours');
    }

};
