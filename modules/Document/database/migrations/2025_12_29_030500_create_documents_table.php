<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // Tipo de documento y origen
            $table->foreignId('type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('proccess')->nullable();
            $table->foreignId('source_id')->nullable()->constrained('document_sources')->nullOnDelete();
            $table->foreignId('load_id')->nullable()->constrained('document_loads')->nullOnDelete();
            $table->foreignId('sync_id')->nullable()->constrained('document_syncs')->nullOnDelete();
            $table->foreignId('upload_id')->nullable()->constrained('document_upload_types')->nullOnDelete();
            $table->foreignId('lang_id')->nullable()->constrained('langs')->nullOnDelete();

            // Estado del documento
            $table->foreignId('status_id')->nullable()->constrained('document_statuses')->nullOnDelete();
            $table->foreignId('sla_policy_id')->nullable()->constrained('document_sla_policies')->nullOnDelete();
            $table->string('validation_status')->nullable();

            // Información del cliente (denormalizado para performance)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_firstname')->nullable();
            $table->string('customer_lastname')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_cellphone')->nullable();
            $table->string('customer_dni')->nullable();
            $table->string('customer_company')->nullable();

            // Información del pedido (denormalizado para performance)
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('order_reference')->nullable();
            $table->dateTime('order_date')->nullable();
            $table->unsignedBigInteger('cart_id')->nullable();

            // Validación en múltiples etapas
            $table->integer('current_stage')->nullable();
            $table->integer('total_stages')->nullable();
            $table->string('current_validator_group')->nullable();
            $table->boolean('requires_financing')->default(false);

            // Fechas de confirmación y recordatorios
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('uploaded_confirmation_sent_at')->nullable();
            $table->dateTime('reminder_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->dateTime('validation_started_at')->nullable();
            $table->dateTime('validation_completed_at')->nullable();

            // Documentos denormalizados (JSON)
            $table->json('required_documents')->nullable();
            $table->json('uploaded_documents')->nullable();
            $table->json('additional_attachments')->nullable();

            // Usuario asignado para validación
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Índices para performance
            $table->index('status_id');
            $table->index('validation_status');
            $table->index(['status_id', 'validation_status']);
            $table->index(['assigned_user_id', 'validation_status']);
            $table->index(['order_id', 'customer_id']);
            $table->index(['validation_started_at', 'validation_completed_at']);
            $table->index('uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
