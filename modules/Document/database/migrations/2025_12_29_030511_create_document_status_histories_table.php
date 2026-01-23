<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Historial de cambios de estado de documentos.
     * Registra todas las transiciones de estado con usuario, razón y metadatos.
     */
    public function up(): void
    {
        Schema::create('document_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->foreignId('from_status_id')
                ->nullable()
                ->constrained('document_statuses')
                ->nullOnDelete();
            $table->foreignId('to_status_id')
                ->constrained('document_statuses')
                ->cascadeOnDelete();
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('reason')->nullable()->comment('Razón del cambio de estado');
            $table->json('metadata')->nullable()->comment('Metadatos adicionales del cambio');
            $table->timestamps();

            $table->index('document_id');
            $table->index('from_status_id');
            $table->index('to_status_id');
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_status_histories');
    }
};
