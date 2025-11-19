<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnRequestBarcodesTable extends Migration
{
    public function up()
    {
        Schema::create('return_request_barcodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('product_id');
            $table->string('barcode_number', 50)->unique();
            $table->enum('barcode_type', ['CODE128', 'QR', 'EAN13'])->default('CODE128');
            $table->string('barcode_image_path')->nullable();
            $table->enum('status', [
                'generated',
                'printed',
                'scanned',
                'validated',
                'rejected',
                'invalidated'
            ])->default('generated');
            $table->timestamp('generated_at');
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('request_id')
                ->references('id')
                ->on('return_requests')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('return_request_products')
                ->onDelete('cascade');

            $table->foreign('scanned_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index(['barcode_number', 'status']);
            $table->index('request_id');
            $table->index('status');
            $table->index('scanned_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_request_barcodes');
    }
}
