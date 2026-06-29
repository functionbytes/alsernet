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
        Schema::create('page_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->unique()->constrained('pages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable()->comment('Session ID para detectar si sesión está activa');
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamp('expires_at')->comment('Cuándo expira el lock (5 minutos)');
            $table->timestamps();

            $table->index('page_id');
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_locks');
    }
};
