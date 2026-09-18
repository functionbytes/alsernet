<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('supplier_source_files');

        Schema::create('supplier_source_files', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->cascadeOnDelete();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('file_type', 50);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->enum('origin', ['ftp', 'sftp', 'upload'])->default('upload');
            $table->enum('extraction_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('extracted_at')->nullable();
            $table->unsignedBigInteger('extraction_result_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('extraction_result_id')
                ->references('id')
                ->on('supplier_extraction_results')
                ->nullOnDelete();

            $table->index(['source_id', 'extraction_status']);
            $table->index(['supplier_id', 'created_at']);
            $table->index('file_type');
            $table->index('origin');
            $table->index('extraction_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_files');
    }
};
