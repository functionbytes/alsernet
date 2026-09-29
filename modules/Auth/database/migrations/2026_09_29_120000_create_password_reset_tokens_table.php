<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla estándar del broker de contraseñas de Laravel (config auth.passwords.users.table).
 * 29-sep-2026: la recuperación de contraseña usaba columnas inexistentes en `users`;
 * ahora usa Password::sendResetLink(), que necesita esta tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
