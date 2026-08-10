<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de cambios sobre la configuración de un Form (helpdesk_forms).
 * Relevante porque un cambio de category_id o un active=false mal hecho
 * desvía o descarta tickets reales de clientes en silencio -- este historial
 * es la forma de rastrear quién lo hizo y cuándo.
 *
 * form_id es nullOnDelete (no cascade): si se elimina el Form, su historial
 * de auditoría sigue siendo consultable (igual que form_field_id en
 * form_submission_values del módulo Forms de "system": el histórico no debe
 * romperse porque la entidad referenciada ya no exista).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_form_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('helpdesk_forms')->nullOnDelete();
            $table->string('form_key'); // denormalizado a propósito: sigue siendo legible si el Form se borra
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable(); // denormalizado: sigue siendo legible si el usuario se borra
            $table->string('action'); // created|updated|activated|deactivated|deleted|imported
            $table->json('changes')->nullable(); // {field: [old, new]}
            $table->timestamp('created_at')->nullable();

            $table->index('form_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_form_changes');
    }
};
