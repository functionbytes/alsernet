<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Registro de correos electrónicos enviados/programados relacionados a documentos.
     * Mantiene historial de confirmaciones, recordatorios y notificaciones.
     */
    public function up(): void
    {
        Schema::create('document_mails', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('document_id')
                ->constrained('documents')
                ->cascadeOnDelete();
            $table->string('email_type')->comment('Tipo de email (confirmation, reminder, notification, etc)');
            $table->string('recipient_email')->comment('Email del destinatario');
            $table->string('subject')->comment('Asunto del email');
            $table->longText('body_html')->comment('Cuerpo HTML del email');
            $table->longText('body_text')->nullable()->comment('Cuerpo en texto plano');
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('mail_templates')
                ->nullOnDelete();
            $table->foreignId('sent_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('metadata')->nullable()->comment('Metadata del email (intentos, tracking, etc)');
            $table->string('status')->default('queued')->comment('Estado (queued, sent, failed, bounced, etc)');
            $table->text('error_message')->nullable()->comment('Mensaje de error si falla el envío');
            $table->timestamp('sent_at')->nullable()->comment('Fecha/hora de envío');
            $table->timestamps();

            $table->index('document_id');
            $table->index('email_type');
            $table->index('status');
            $table->index('recipient_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_mails');
    }
};
