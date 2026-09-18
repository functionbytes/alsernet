<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_file_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('url');
            $table->string('disk');
            $table->string('file_hash', 64)->nullable();
            $table->integer('version_number');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['media_file_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_file_versions');
    }
};
