<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns_warranty_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_warranty_id')->constrained('warranties')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('warranty_type_id')->constrained('warranty_types')->onDelete('restrict');

            // Información de la extensión
            $table->string('extension_number')->unique();
            $table->integer('additional_months');
            $table->date('extension_start_date');
            $table->date('extension_end_date');
            $table->decimal('extension_cost', 10, 2);
            $table->string('payment_status')->default('pending'); // pending, paid, failed
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            // Estado
            $table->string('status')->default('pending'); // pending, active, expired, cancelled
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();

            // Términos específicos de la extensión
            $table->json('extension_terms')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();

            // Metadatos
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['original_warranty_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('extension_number');
            $table->index(['payment_status', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns_warranty_extensions');
    }
};
