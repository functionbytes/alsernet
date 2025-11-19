<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnDiscussionsTable extends Migration
{
    public function up()
    {
        Schema::create('return_discussions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('employee_id')->default(0);
            $table->text('message');
            $table->string('file_name')->nullable();
            $table->boolean('private')->default(false);
            $table->timestamps();
            $table->foreign('request_id')->references('id')->on('return_requests')->onDelete('cascade');
            $table->index(['request_id']);
            $table->index(['employee_id']);
            $table->index(['private']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_discussions');
    }
}
