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
        Schema::create('supplier_source_templates', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();

            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('source_type', 50);

            // Configuraciones template (sin credenciales)
            $table->json('connection_template')->nullable();
            $table->json('extraction_template')->nullable();
            $table->json('schedule_template')->nullable();
            $table->json('retry_template')->nullable();
            $table->json('validation_template')->nullable();

            // Variables requeridas
            $table->json('required_variables')->nullable();

            // Categorización
            $table->string('category', 50)->nullable(); // 'ecommerce', 'manufacturer', 'distributor', 'marketplace'
            $table->json('tags')->nullable();

            // Uso
            $table->integer('usage_count')->default(0);
            $table->boolean('is_public')->default(false); // Compartir entre organizaciones

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            // Índices
            $table->index('source_type', 'idx_source_templates_type');
            $table->index('category', 'idx_source_templates_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_templates');
    }
};
