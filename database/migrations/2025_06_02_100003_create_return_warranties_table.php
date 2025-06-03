<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->string('warranty_number')->unique(); // Número único de garantía
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warranty_type_id')->constrained('warranty_types')->onDelete('restrict');
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Propietario

            // Información del producto garantizado
            $table->string('product_serial_number')->nullable();
            $table->string('product_model')->nullable();
            $table->decimal('product_price', 10, 2);
            $table->integer('quantity')->default(1);

            // Fechas de garantía
            $table->date('purchase_date');
            $table->date('warranty_start_date');
            $table->date('warranty_end_date');
            $table->integer('warranty_duration_months');

            // Estado y validez
            $table->string('status')->default('active'); // active, expired, claimed, cancelled, transferred
            $table->boolean('is_registered_with_manufacturer')->default(false);
            $table->string('manufacturer_warranty_id')->nullable(); // ID en sistema del fabricante
            $table->timestamp('manufacturer_registration_date')->nullable();

            // Información de activación
            $table->date('activation_date')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('activation_details')->nullable();

            // Transferencia
            $table->foreignId('original_owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('transferred_at')->nullable();
            $table->json('transfer_history')->nullable();

            // Costos
            $table->decimal('warranty_cost', 10, 2)->default(0.00);
            $table->boolean('is_paid')->default(true);
            $table->timestamp('payment_date')->nullable();

            // Documentos y evidencias
            $table->json('documents')->nullable(); // URLs de documentos subidos
            $table->json('proof_of_purchase')->nullable();

            // Términos y condiciones
            $table->json('terms_and_conditions')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();

            // Metadatos
            $table->json('metadata')->nullable(); // Información adicional específica
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index(['warranty_end_date', 'status']);
            $table->index(['manufacturer_id', 'is_registered_with_manufacturer']);
            $table->index('warranty_number');
            $table->index('product_serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
