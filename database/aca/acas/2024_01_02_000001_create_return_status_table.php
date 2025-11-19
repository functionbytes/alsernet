<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnStatusTable extends Migration
{
    public function up()
    {
        Schema::create('return_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('state_id');
            $table->string('color', 32)->nullable();
            $table->boolean('send_email')->default(false);
            $table->boolean('is_pickup')->nullable()->default(false);
            $table->boolean('is_received')->nullable()->default(false);
            $table->boolean('is_refunded')->nullable()->default(false);
            $table->boolean('shown_to_customer')->default(true);
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->foreign('state_id')->references('id')->on('return_states')->onDelete('cascade');
            $table->index(['active']);
            $table->index(['shown_to_customer']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_status');
    }
}
