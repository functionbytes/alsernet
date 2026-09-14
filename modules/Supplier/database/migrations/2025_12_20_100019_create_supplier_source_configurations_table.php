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
        Schema::create('supplier_source_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');

            // Tipo de configuración
            $table->string('config_type', 50); // 'connection', 'authentication', 'extraction', 'schedule', 'retry', 'proxy', 'validation'

            // Configuración JSON validada por esquema
            $table->json('config_data')->nullable();
            $table->string('config_schema_version', 10)->default('1.0');

            // Validación
            $table->boolean('is_valid')->default(true);
            $table->json('validation_errors')->nullable();
            $table->timestamp('last_validated_at')->nullable();

            // Estado
            $table->boolean('is_enabled')->default(true);
            $table->integer('priority')->default(0); // Para configuraciones del mismo tipo

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            // Índices
            $table->unique(['source_id', 'config_type'], 'idx_86911');
            $table->index('source_id', 'idx_source_configs_source');
            $table->index('config_type', 'idx_source_configs_type');
            $table->index('is_enabled', 'idx_source_configs_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_configurations');
    }
};
