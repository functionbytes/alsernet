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
        Schema::create('supplier_source_health_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('supplier_sources')->onDelete('cascade');

            $table->string('check_type', 30); // 'connectivity', 'authentication', 'structure', 'content'

            $table->boolean('is_success');
            $table->integer('status_code')->nullable();
            $table->integer('response_time_ms')->nullable();

            $table->string('error_type', 100)->nullable();
            $table->text('error_message')->nullable();

            // Snapshot de datos
            $table->integer('page_size_bytes')->nullable();
            $table->integer('products_found')->nullable();

            $table->timestamp('checked_at')->useCurrent();

            // Índices
            $table->index(['source_id', 'checked_at'], 'idx_health_history_source_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_source_health_history');
    }
};
