<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnRequestStoresTable extends Migration
{
    public function up()
    {
        Schema::create('return_request_stores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('location_id');
            $table->date('expected_delivery_date');
            $table->timestamp('actual_delivery_date')->nullable();
            $table->string('received_by')->nullable();
            $table->string('confirmation_code')->nullable();
            $table->enum('status', ['scheduled', 'delivered', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('return_requests')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('store_locations');

            $table->index(['location_id', 'expected_delivery_date']);
            $table->unique('return_request_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_request_stores');
    }
}


