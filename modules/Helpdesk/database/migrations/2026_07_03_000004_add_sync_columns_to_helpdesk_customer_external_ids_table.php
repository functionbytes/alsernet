<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadata de sincronizacion por vinculo (no por proveedor): cuando se
 * reverifico por ultima vez este external_id y si sigue siendo valido.
 * Usado por HelpdeskIntegration\Services\CustomerIntegrationService::syncPlatform().
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customer_external_ids', function (Blueprint $table) {
            $table->timestamp('last_synced_at')->nullable()->after('metadata');
            $table->string('sync_status', 20)->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customer_external_ids', function (Blueprint $table) {
            $table->dropColumn(['last_synced_at', 'sync_status']);
        });
    }
};
