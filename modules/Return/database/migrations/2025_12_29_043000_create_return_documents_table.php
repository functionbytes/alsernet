<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->cascadeOnDelete();
            $table->string('document_type')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('downloaded_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->index('return_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_documents');
    }
};
