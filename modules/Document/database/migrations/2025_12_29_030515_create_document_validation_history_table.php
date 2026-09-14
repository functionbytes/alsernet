<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Historial de validaciones de documentos.
     * Registra todas las acciones de validación con usuario, etapa, comentarios y timestamps.
     */
    public function up(): void
    {
        Schema::create('document_validation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->integer('stage_number')->default(1)->comment('Etapa de validación');
            $table->string('validator_group')->comment('Nombre del grupo validador');
            $table->foreignId('validator_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action')->comment('Acción realizada (approved, rejected, pending, etc)');
            $table->text('comments')->nullable()->comment('Comentarios del validador');
            $table->timestamp('validated_at')->nullable()->comment('Fecha/hora de la validación');
            $table->timestamps();

            $table->index('document_id');
            $table->index('validator_group');
            $table->index('stage_number');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validation_history');
    }
};
