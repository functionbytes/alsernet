<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnRequestDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('return_request_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->enum('document_type', [
                'shipping_label',
                'return_slip',
                'barcode_sheet',
                'customer_receipt',
                'carrier_manifest'
            ]);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->default('application/pdf');
            $table->integer('file_size')->unsigned();
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamp('downloaded_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('request_id')
                ->references('id')
                ->on('return_requests')
                ->onDelete('cascade');

            // Indexes
            $table->index(['request_id', 'document_type']);
            $table->index('generated_at');
        });
    }


    public function down()
    {
        Schema::dropIfExists('return_request_documents');
    }
}
