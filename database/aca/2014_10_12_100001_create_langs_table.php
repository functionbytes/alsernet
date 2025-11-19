<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLangsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('langs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slack', 30)->unique();
            $table->string('title');
            $table->string('iso_code');
            $table->string('lenguage_code');
            $table->string('locate');
            $table->string('date_format_full');
            $table->string('date_format_lite');
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
        Schema::dropIfExists('langs');
    }
}
