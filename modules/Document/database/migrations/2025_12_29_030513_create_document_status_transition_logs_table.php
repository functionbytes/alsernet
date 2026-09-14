<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Log detallado de transiciones de estado de documentos.
     * Registra cada transición autorizada con usuario que la ejecutó.
     */
    public function up(): void
    {
        Schema::create('document_status_transition_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->foreignId('transition_id')
                ->constrained('document_status_transitions')
                ->cascadeOnDelete();
            $table->foreignId('from_status_id')
                ->constrained('document_statuses')
                ->cascadeOnDelete();
            $table->foreignId('to_status_id')
                ->constrained('document_statuses')
                ->cascadeOnDelete();
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('reason')->nullable()->comment('Razón o descripción de la transición');
            $table->json('metadata')->nullable()->comment('Metadatos adicionales');
            $table->timestamps();

            $table->index('document_id');
            $table->index('transition_id');
            $table->index('performed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_status_transition_logs');
    }
};
