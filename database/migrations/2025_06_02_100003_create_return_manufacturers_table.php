<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->json('warranty_policies')->nullable(); // Políticas específicas
            $table->string('api_endpoint')->nullable(); // Para integración
            $table->string('api_key')->nullable();
            $table->json('api_config')->nullable(); // Configuración adicional de API
            $table->boolean('has_api_integration')->default(false);
            $table->boolean('auto_warranty_registration')->default(false);
            $table->string('warranty_lookup_url')->nullable(); // URL para consultar garantías
            $table->integer('default_warranty_months')->default(12);
            $table->json('support_contact_info')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index(['is_active', 'has_api_integration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
