<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los comandos de purga (helpdeskintegration:purge-audit-log,
 * helpdeskintegration:purge-identity-verifications) filtran por
 * `WHERE created_at < X` sin `customer_id` — los únicos índices existentes
 * son compuestos con `customer_id` primero ((customer_id, created_at) y
 * (customer_id, verified_at)), que MySQL/MariaDB no puede usar para un
 * filtro que empieza por la segunda columna. Sin índice propio en
 * `created_at`, la purga hace un full table scan; con retención larga
 * (180 días de audit log) y volumen alto (cada link/unlink/sync/verificación
 * crea una fila), esto crece sin límite con el histórico.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_integration_audit_log', function (Blueprint $table) {
            $table->index('created_at', 'hial_created_at_idx');
        });

        Schema::connection($this->connection)->table('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->index('created_at', 'hciv_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_integration_audit_log', function (Blueprint $table) {
            $table->dropIndex('hial_created_at_idx');
        });

        Schema::connection($this->connection)->table('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->dropIndex('hciv_created_at_idx');
        });
    }
};
