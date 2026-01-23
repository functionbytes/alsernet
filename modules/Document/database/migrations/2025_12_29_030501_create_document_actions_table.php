<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Acciones realizadas en documentos (email enviado, recordatorio, validación, etc).
     * Puede ser realizada por usuario, sistema, o job automático.
     */
    public function up(): void
    {
        Schema::create('document_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->string('action_type')->comment('Tipo de acción (email, reminder, validation, etc)');
            $table->string('action_name')->comment('Nombre descriptivo de la acción');
            $table->text('description')->nullable()->comment('Descripción detallada');
            $table->json('metadata')->nullable()->comment('Metadatos adicionales');
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que realizó la acción (null si es sistema)');
            $table->string('performed_by_type')->default('system')->comment('Tipo de ejecutor (system, user, job)');
            $table->timestamps();

            $table->index('document_id');
            $table->index('action_type');
            $table->index('performed_by_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_actions');
    }
};
