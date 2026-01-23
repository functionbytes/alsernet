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
        Schema::create('supplier_source_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 26)->unique();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');

            $table->string('name', 100);
            $table->text('description')->nullable();

            // Endpoint
            $table->string('endpoint_path', 255); // Ej: /webhooks/supplier/nike/inventaries
            $table->string('secret_key', 255)->nullable(); // Para validar firma

            // Eventos soportados
            $table->json('events')->nullable();

            // Mapeo de payload
            $table->json('payload_mapping');

            // Procesamiento
            $table->string('processing_mode', 30)->default('async'); // 'sync', 'async', 'batch'
            $table->integer('batch_size')->default(100);
            $table->integer('batch_window_seconds')->default(60);

            // Autenticación esperada
            $table->string('auth_type', 30)->nullable(); // 'signature', 'bearer', 'basic', 'none'
            $table->json('auth_config')->nullable();

            // Estado
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->integer('total_received')->default(0);

            $table->timestamps();

            // Índices
            $table->index('source_id', 'idx_webhooks_source');
            $table->index('endpoint_path', 'idx_webhooks_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_webhooks');
    }
};
