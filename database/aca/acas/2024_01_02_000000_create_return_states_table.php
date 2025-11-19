<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnStatesTable extends Migration
{
    public function up()
    {
        Schema::create('return_states', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->timestamps();

            $table->unique('name');
            $table->index(['name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_states');
    }
}
