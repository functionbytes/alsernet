<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterListsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('newsletter_lists_users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('newsletter_id')->unsigned();
                $table->unsignedBigInteger('list_id')->unsigned();
                $table->foreign('newsletter_id')->references('id')->on('newsletters')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('list_id')->references('id')->on('newsletter_lists')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('newsletter_lists_users');
    }
}
