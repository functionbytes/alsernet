<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Notas internas y comentarios sobre documentos.
     * Permite registrar notas privadas (internas) o visibles al cliente.
     */
    public function up(): void
    {
        Schema::create('document_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->longText('content')->comment('Contenido de la nota');
            $table->boolean('is_internal')->default(true)->comment('Verdadero si es nota interna, falso si es visible al cliente');
            $table->timestamps();

            $table->index('document_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_notes');
    }
};
