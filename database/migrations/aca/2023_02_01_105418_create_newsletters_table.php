<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewslettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slack', 30)->unique();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email');
            $table->string('ids_sports');
            $table->tinyInteger('erp')->default(0);
            $table->tinyInteger('lopd')->default(0);
            $table->tinyInteger('none')->default(0);
            $table->tinyInteger('sports')->default(0);
            $table->tinyInteger('parties')->default(0);
            $table->tinyInteger('suscribe')->default(0);
            $table->tinyInteger('check')->default(0);
            $table->unsignedBigInteger('lang_id');
            $table->foreign('lang_id')->references('id')->on('langs')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('check_at')->nullable();
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
        Schema::dropIfExists('newsletters');
    }
}
