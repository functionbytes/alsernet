<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una fila por cada apertura registrada (no un contador ni un
        // opened_at único) — así "2 aperturas · última 14:22" se puede
        // reconstruir con count()/max() sin perder el resto del historial.
        Schema::create('email_log_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained('email_logs')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('opened_at');

            $table->index(['email_log_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_log_opens');
    }
};
