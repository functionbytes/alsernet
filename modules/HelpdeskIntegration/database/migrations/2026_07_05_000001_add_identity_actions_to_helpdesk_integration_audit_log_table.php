<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende el enum `action` de `helpdesk_integration_audit_log` para
 * registrar tambien eventos del gate de verificacion de identidad
 * (identity_verified/identity_locked), que hasta ahora solo vivian en
 * helpdesk_customer_identity_verifications (purgada a los 30 dias).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_integration_audit_log')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_integration_audit_log
             MODIFY COLUMN action ENUM('linked', 'unlinked', 'synced', 'identity_verified', 'identity_locked') NOT NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_integration_audit_log')) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_integration_audit_log
             MODIFY COLUMN action ENUM('linked', 'unlinked', 'synced') NOT NULL"
        );
    }
};
