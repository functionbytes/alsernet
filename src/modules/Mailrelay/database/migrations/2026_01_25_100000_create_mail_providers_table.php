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
        Schema::create('mail_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre visible (ej: "Mailrelay Production")
            $table->string('driver'); // mailrelay, mailtrap, sendgrid, etc.
            $table->text('credentials'); // API keys, tokens, config (encrypted)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Provider por defecto
            $table->integer('priority')->default(0); // Para failover (mayor = más prioridad)
            $table->json('limits')->nullable(); // Rate limits personalizados
            $table->json('metadata')->nullable(); // Configuración adicional
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('connection_ok')->default(false);
            $table->timestamps();

            // Índices
            $table->index(['driver', 'is_active']);
            $table->index('is_default');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_providers');
    }
};
