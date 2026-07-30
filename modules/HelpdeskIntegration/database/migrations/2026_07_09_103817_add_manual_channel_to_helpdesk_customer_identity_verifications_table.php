<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade el canal 'manual' (agente confirma la identidad sin enviar codigo)
 * al enum existente, junto a 'email'/'sms'.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_customer_identity_verifications MODIFY channel ENUM('email', 'sms', 'manual') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::connection($this->connection)->statement(
            "ALTER TABLE helpdesk_customer_identity_verifications MODIFY channel ENUM('email', 'sms') NOT NULL"
        );
    }
};
