<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_requirements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->string('key');
            $table->boolean('is_required')->default(false);
            $table->boolean('accepts_multiple')->default(false);
            $table->integer('max_file_size')->nullable();
            $table->json('allowed_extensions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['document_type_id', 'key'], 'idx_7710');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_requirements');
    }
};
