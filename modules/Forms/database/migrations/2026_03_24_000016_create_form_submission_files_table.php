<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->index('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_files');
    }
};
