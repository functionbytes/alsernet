<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('return_product_id')->nullable()->constrained('return_request_products')->cascadeOnDelete();
            $table->string('barcode_number')->unique();
            $table->string('barcode_type')->nullable();
            $table->string('barcode_image_path')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('printed_at')->nullable();
            $table->dateTime('scanned_at')->nullable();
            $table->foreignId('scanned_by')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();
            $table->index('return_request_id');
            $table->index('barcode_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_barcodes');
    }
};
