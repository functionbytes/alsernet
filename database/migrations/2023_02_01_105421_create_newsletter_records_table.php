<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('subscribers_records', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('old_value');
                $table->string('new_value');
                $table->unsignedBigInteger('condition_id')->unsigned();
                $table->foreign('condition_id')->references('id')->on('subscribers_conditions')->onUpdate('cascade')->onDelete('cascade');
                $table->timestamp('synced_at')->nullable();
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
        Schema::dropIfExists('subscribers_records');
    }
}
