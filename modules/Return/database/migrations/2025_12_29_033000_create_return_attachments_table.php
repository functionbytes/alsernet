<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_attachments', function (Blueprint $table) {
            $table->id('id_return_attachment');
            $table->foreignId('id_return_request')->constrained('return_requests')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->integer('file_size');
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
            $table->index('id_return_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_attachments');
    }
};
