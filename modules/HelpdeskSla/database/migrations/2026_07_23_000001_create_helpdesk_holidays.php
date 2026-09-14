<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Días festivos del calendario de negocio: el motor de horas hábiles (SLA y
 * escalado) los trata como días no laborables, empujando los vencimientos.
 * `is_recurring` marca festivos anuales fijos (se comparan por mes-día).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_holidays')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_holidays');
    }
};
