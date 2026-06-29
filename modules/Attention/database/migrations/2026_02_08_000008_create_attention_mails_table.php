<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attention_mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attention_id')
                ->constrained('attentions')
                ->onDelete('cascade');
            $table->string('email_type', 100)->comment('Tipo de email');
            $table->string('recipient', 150)->comment('Destinatario');
            $table->string('subject', 255)->comment('Asunto del email');
            $table->longText('body')->nullable()->comment('Cuerpo del email');
            $table->timestamp('sent_at')->nullable()->comment('Fecha de envío exitoso');
            $table->timestamp('failed_at')->nullable()->comment('Fecha de fallo');
            $table->text('error_message')->nullable()->comment('Mensaje de error si falló');
            $table->timestamps();

            $table->index('attention_id');
            $table->index('email_type');
            $table->index('sent_at');
            $table->index('failed_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_mails');
    }
};
