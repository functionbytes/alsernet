<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende el enum `action` con los dos resultados del vínculo automático.
 *
 * Hasta ahora el log solo registraba lo que hacía un agente a mano (linked /
 * unlinked / synced) y los eventos del gate de identidad. La búsqueda
 * automática al entrar un correo no dejaba ninguna traza: 'link_failed' cubre
 * el caso "se buscó y el cliente no está en la plataforma", y 'link_error' el
 * caso "la plataforma no respondió", que son cosas distintas para quien
 * después revisa por qué un cliente no está vinculado.
 *
 * Mismo patrón que la migración que añadió identity_verified/identity_locked.
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
             MODIFY COLUMN action ENUM('linked', 'unlinked', 'synced', 'identity_verified', 'identity_locked', 'link_failed', 'link_error') NOT NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('helpdesk_integration_audit_log')) {
            return;
        }

        DB::connection($this->connection)
            ->table('helpdesk_integration_audit_log')
            ->whereIn('action', ['link_failed', 'link_error'])
            ->delete();

        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_integration_audit_log
             MODIFY COLUMN action ENUM('linked', 'unlinked', 'synced', 'identity_verified', 'identity_locked') NOT NULL"
        );
    }
};
