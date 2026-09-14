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
        if (Schema::hasTable('erp_endpoints')) {
            return;
        }

        Schema::create('erp_endpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable(); // Multi-tenant support (optional)
            $table->foreignId('credential_id')->nullable()->constrained('erp_credentials')->onDelete('set null'); // Credencial seleccionada
            $table->string('name'); // ej: "Gestión Central - Clientes"
            $table->string('slug')->unique(); // ej: "gc-clientes"
            $table->string('url'); // URL del endpoint
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->default('POST');
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(30); // seconds
            $table->integer('retry_attempts')->default(3);
            $table->text('description')->nullable();
            $table->json('headers')->nullable(); // Custom headers
            $table->json('query_params')->nullable(); // Default query params
            $table->string('content_type')->default('application/json');
            $table->integer('rate_limit')->nullable(); // requests per minute
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
            $table->index('slug');
            $table->index('credential_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_endpoints');
    }
};
