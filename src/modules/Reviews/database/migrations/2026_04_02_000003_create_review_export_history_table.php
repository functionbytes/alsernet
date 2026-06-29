<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_export_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('filters')->nullable();
            $table->string('format', 10);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_export_history');
    }
};
