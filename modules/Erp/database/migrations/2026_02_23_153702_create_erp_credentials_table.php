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
        Schema::create('erp_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable(); // Multi-tenant support
            $table->string('name'); // ej: "Gestión - Basic Auth", "Oracle API Key"
            $table->text('description')->nullable(); // Descripción de la credencial
            $table->enum('auth_type', ['none', 'basic', 'bearer', 'api_key', 'custom'])->default('none');
            $table->string('username')->nullable(); // For basic auth
            $table->text('password')->nullable(); // Encrypted, for basic auth
            $table->text('token')->nullable(); // Encrypted, for bearer auth
            $table->text('api_key')->nullable(); // Encrypted, for api_key auth
            $table->string('api_key_header')->default('X-API-Key'); // Header name for API key
            $table->json('custom_headers')->nullable(); // For custom auth
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_credentials');
    }
};
