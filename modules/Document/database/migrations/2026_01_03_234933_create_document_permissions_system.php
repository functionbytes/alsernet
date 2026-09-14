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
        // Tabla de permisos disponibles (similar a Spatie Permission)
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Ej: 'approve-documents', 'upload-files'
            $table->string('label'); // Ej: 'Aprobar documentos', 'Subir archivos'
            $table->string('category')->nullable(); // Ej: 'validation', 'files', 'emails'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Tabla pivot: relación many-to-many entre grupos y permisos
        Schema::create('document_validator_group_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('validator_group_id')
                ->constrained('document_validator_groups')
                ->onDelete('cascade');
            $table->foreignId('permission_id')
                ->constrained('document_permissions')
                ->onDelete('cascade');
            $table->timestamps();

            // Un grupo no puede tener el mismo permiso dos veces
            $table->unique(['validator_group_id', 'permission_id'], 'group_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validator_group_permission');
        Schema::dropIfExists('document_permissions');
    }
};
