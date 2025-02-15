<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('subscribers_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('condition_id')->unsigned();
                $table->unsignedBigInteger('subscribers_id')->unsigned();
                $table->foreign('condition_id')->references('id')->on('subscribers_conditions')->onUpdate('cascade')->onDelete('cascade');
                $table->foreign('subscribers_id')->references('id')->on('newsletters')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('subscribers_jobs');
    }
}
