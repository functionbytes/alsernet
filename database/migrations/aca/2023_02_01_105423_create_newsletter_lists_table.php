<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('subscribers_lists', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('slack', 30)->unique();
                $table->string('title');
                $table->tinyInteger('available')->default(0);
                $table->unsignedBigInteger('lang_id')->unsigned();
                $table->foreign('lang_id')->references('id')->on('langs')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('subscribers_lists');
    }
}
