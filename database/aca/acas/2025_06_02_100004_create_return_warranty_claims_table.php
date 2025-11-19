<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('warranty_id')->constrained('warranties')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Información del problema
            $table->string('issue_category'); // hardware, software, defect, damage, etc.
            $table->text('issue_description');
            $table->date('issue_occurred_date');
            $table->json('symptoms')->nullable(); // Lista de síntomas

            // Estado del reclamo
            $table->string('status')->default('submitted'); // submitted, under_review, approved, rejected, in_repair, completed, cancelled
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->decimal('estimated_repair_cost', 10, 2)->nullable();
            $table->text('resolution_description')->nullable();

            // Asignación
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->string('assigned_department')->nullable(); // technical, manufacturer, third_party

            // Integración con fabricante
            $table->string('manufacturer_claim_id')->nullable();
            $table->string('manufacturer_status')->nullable();
            $table->json('manufacturer_response')->nullable();
            $table->timestamp('submitted_to_manufacturer_at')->nullable();
            $table->timestamp('manufacturer_response_at')->nullable();

            // Resolución
            $table->string('resolution_type')->nullable(); // repair, replace, refund, reject
            $table->decimal('resolution_cost', 10, 2)->default(0.00);
            $table->text('customer_satisfaction_notes')->nullable();
            $table->integer('customer_rating')->nullable(); // 1-5
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');

            // Seguimiento
            $table->json('status_history')->nullable(); // Historial de cambios de estado
            $table->json('communication_log')->nullable(); // Log de comunicaciones
            $table->json('attachments')->nullable(); // Fotos, videos, documentos

            // SLA y tiempos
            $table->timestamp('response_due_date')->nullable();
            $table->timestamp('resolution_due_date')->nullable();
            $table->boolean('sla_met')->nullable();
            $table->integer('total_resolution_hours')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['warranty_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'priority']);
            $table->index('claim_number');
            $table->index('manufacturer_claim_id');
            $table->index(['resolution_due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
