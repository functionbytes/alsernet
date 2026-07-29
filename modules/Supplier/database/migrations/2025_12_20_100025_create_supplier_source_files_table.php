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
        Schema::create('supplier_source_files', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('supplier_extraction_batches');

            // Archivo
            $table->string('original_filename', 255);
            $table->string('stored_path', 500);
            $table->bigInteger('file_size_bytes');
            $table->string('mime_type', 100)->nullable();

            // Hash para detectar duplicados
            $table->string('file_hash', 64);

            // Procesamiento
            $table->string('status', 30)->default('pending'); // 'pending', 'processing', 'processed', 'failed', 'archived'

            $table->integer('rows_total')->nullable();
            $table->integer('rows_processed')->default(0);
            $table->integer('rows_success')->default(0);
            $table->integer('rows_failed')->default(0);

            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->text('processing_error')->nullable();

            // Origen
            $table->string('upload_type', 30); // 'manual', 'ftp', 'email', 'api'
            $table->foreignId('uploaded_by')->nullable()->constrained('users');

            // Retención
            $table->boolean('delete_after_processed')->default(false);
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index('source_id', 'idx_source_files_source');
            $table->index('status', 'idx_source_files_status');
            $table->index('file_hash', 'idx_source_files_hash');
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
