<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_status_id')->constrained('document_statuses')->cascadeOnDelete();
            $table->foreignId('to_status_id')->constrained('document_statuses')->cascadeOnDelete();
            $table->string('permission')->nullable();
            $table->boolean('requires_all_documents_uploaded')->default(false);
            $table->integer('auto_transition_after_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['from_status_id', 'to_status_id'], 'idx_2245');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_status_transitions');
    }
};
