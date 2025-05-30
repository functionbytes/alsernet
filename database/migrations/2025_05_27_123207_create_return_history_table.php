<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('return_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('status_id');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('employee_id')->default(1);
            $table->boolean('set_pickup')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->boolean('shown_to_customer')->default(true);
            $table->timestamps();

            $table->foreign('request_id')->references('id')->on('return_requests')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('return_status');

            $table->index(['request_id']);
            $table->index(['status_id']);
            $table->index(['employee_id']);
            $table->index(['shown_to_customer']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_history');
    }
}
