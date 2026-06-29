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
        Schema::create('document_type_validation_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types')->onDelete('cascade');
            $table->string('key')->index(); // pending, in_review, approved, rejected
            $table->integer('order'); // Sort order within the type
            $table->json('conditions')->nullable(); // Optional conditions for this stage
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Unique constraint per type
            $table->unique(['document_type_id', 'key']);
            $table->unique(['document_type_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_type_validation_stages');
    }
};
