<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado de la última búsqueda del cliente en el ERP.
 *
 * Hasta ahora, cuando ErpCustomerLinkerService no encontraba al cliente solo
 * dejaba un Log::info: no había forma de distinguir "se buscó y no está" de
 * "no se buscó nunca". Estas dos columnas resuelven dos cosas a la vez:
 *
 *  - la UI del inbox y del ticket pintan el aviso "Sin cliente en gestión"
 *    sin escanear helpdesk_integration_audit_log en cada carga;
 *  - LinkCustomerToErpJob usa erp_lookup_at como enfriamiento, para no
 *    consultar el ERP en cada correo de un remitente que no es cliente.
 *
 * El vínculo en sí sigue viviendo en helpdesk_customer_external_ids
 * (platform='erp'); aquí solo se guarda el resultado del intento.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_customers')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'erp_lookup_status')) {
                $table->enum('erp_lookup_status', ['linked', 'not_found', 'error'])
                    ->nullable()
                    ->after('internal_notes');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_customers', 'erp_lookup_at')) {
                $table->timestamp('erp_lookup_at')->nullable()->after('erp_lookup_status');
            }
        });

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->index(['erp_lookup_status', 'erp_lookup_at'], 'helpdesk_customers_erp_lookup_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_customers')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropIndex('helpdesk_customers_erp_lookup_idx');
            $table->dropColumn(['erp_lookup_status', 'erp_lookup_at']);
        });
    }
};
