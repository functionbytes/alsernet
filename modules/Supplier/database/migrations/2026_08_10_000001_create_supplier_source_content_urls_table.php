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
        Schema::create('supplier_source_content_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');
            $table->string('url', 2048)->comment('Página donde se espera encontrar contenido/ficha del proveedor');
            $table->string('note', 255)->nullable()->comment('Ej: "fichas técnicas", "catálogo PDF"');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(10)->comment('Orden de prioridad al sugerir fuentes a la IA (1 = más alto)');
            $table->timestamps();

            $table->index(['source_id', 'is_active'], 'idx_source_content_urls_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_content_urls');
    }
};
