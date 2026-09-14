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
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del schedule
            $table->boolean('enabled')->default(true); // ¿Está activo?
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->time('scheduled_time')->default('02:00:00'); // Hora del backup
            $table->json('days_of_week')->nullable(); // Días de la semana (0-6)
            $table->json('days_of_month')->nullable(); // Días del mes (1-31)
            $table->integer('custom_interval_hours')->nullable(); // Para interval custom en horas
            $table->json('backup_types')->nullable(); // Tipos de backup a hacer
            $table->timestamp('last_run_at')->nullable(); // Último backup ejecutado
            $table->timestamp('next_run_at')->nullable(); // Próximo backup programado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
