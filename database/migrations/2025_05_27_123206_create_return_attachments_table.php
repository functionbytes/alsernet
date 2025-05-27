<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('return_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('file_size');
            $table->enum('uploaded_by', ['customer', 'admin'])->default('customer');
            $table->timestamps();

            $table->foreign('request_id')->references('request_id')->on('return_requests')->onDelete('cascade');

            $table->index(['request_id']);
            $table->index(['mime_type']);
            $table->index(['uploaded_by']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_attachments');
    }
}
