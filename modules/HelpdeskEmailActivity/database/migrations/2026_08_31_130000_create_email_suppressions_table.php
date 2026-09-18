<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direcciones a las que nunca se les vuelve a enviar correo automático ni
 * manual — ver EnforceEmailSuppression (listener de MessageSending) y
 * EmailSuppressionService.
 *
 * `module` NOT NULL default '' se usa como centinela de "supresión global"
 * en vez de NULL: un UNIQUE(email, module) con NULL permite duplicados en
 * MySQL/sqlite (NULL != NULL en índices únicos), y con '' el índice único
 * funciona sin lógica de dedup extra en la aplicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('module', 100)->default('');
            $table->string('reason', 20); // hard_bounce|complaint|unsubscribed|manual
            $table->nullableMorphs('causer'); // null = añadida automáticamente por el sistema
            $table->foreignId('email_log_id')->nullable()->constrained('email_logs')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['email', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
    }
};
