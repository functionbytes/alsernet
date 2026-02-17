<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attentions', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->uuid('uid')->unique()->comment('Identificador único interno');
            $table->string('radicado', 50)->unique()->comment('PQRSF-YYYY-NNNNNN');

            // Tipo y categorización
            $table->foreignId('type_id')
                ->constrained('attention_types')
                ->comment('Tipo: P/Q/R/S/F');
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('attention_categories')
                ->onDelete('set null')
                ->comment('Categoría temática');
            $table->foreignId('sede_id')
                ->nullable()
                ->constrained('attention_sedes')
                ->onDelete('set null')
                ->comment('Sede donde se radicó');

            // Datos del ciudadano
            $table->string('customer_firstname', 150)->nullable();
            $table->string('customer_lastname', 150)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_cellphone', 50)->nullable();
            $table->string('customer_dni', 50)->nullable();
            $table->string('customer_address', 255)->nullable();
            $table->boolean('is_anonymous')->default(false)->comment('PQRSF anónimo');

            // Contenido
            $table->string('subject', 255)->comment('Asunto');
            $table->longText('description')->comment('Descripción detallada');

            // Estado y flujo
            $table->string('status', 50)->default('received')->comment('received, in_process, resolved, closed');
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('attention_departments')
                ->onDelete('set null')
                ->comment('Departamento asignado');
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuario responsable');

            // Resolución
            $table->string('response_type', 50)->nullable()->comment('email, presencial, correo_fisico, telefono, no_requiere');
            $table->longText('resolution')->nullable()->comment('Respuesta oficial');
            $table->timestamp('resolved_at')->nullable()->comment('Fecha de resolución');
            $table->timestamp('closed_at')->nullable()->comment('Fecha de cierre');

            // Satisfacción
            $table->tinyInteger('satisfaction_rating')->unsigned()->nullable()->comment('Rating 1-5 estrellas');

            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index('uid');
            $table->index('radicado');
            $table->index('type_id');
            $table->index('category_id');
            $table->index('sede_id');
            $table->index('status');
            $table->index('department_id');
            $table->index('assigned_user_id');
            $table->index('is_anonymous');
            $table->index('customer_email');
            $table->index('customer_dni');
            $table->index(['resolved_at', 'closed_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attentions');
    }
};
