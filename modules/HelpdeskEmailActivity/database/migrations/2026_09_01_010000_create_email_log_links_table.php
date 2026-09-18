<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un registro por cada enlace único reescrito dentro de un envío (ver
        // LogEmailQueued::injectClickTracking) — el clic en sí se guarda
        // aparte en email_log_clicks (una fila por hit, sin dedup, mismo
        // criterio que email_log_opens) para no perder el historial de
        // repeticiones sobre el mismo enlace.
        Schema::create('email_log_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained('email_logs')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->text('url');
            $table->timestamp('created_at');

            $table->index('email_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_log_links');
    }
};
