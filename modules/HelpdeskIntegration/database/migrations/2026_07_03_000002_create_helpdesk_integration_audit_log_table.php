<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de auditoria de acciones sobre integraciones de cliente
 * (vincular/desvincular/sincronizar), quien la hizo y cuando.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_integration_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('helpdesk_customers')->cascadeOnDelete();
            $table->string('platform', 50);
            $table->enum('action', ['linked', 'unlinked', 'synced']);
            $table->string('external_id', 191)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_integration_audit_log');
    }
};
