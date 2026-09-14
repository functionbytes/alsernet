<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversación desde la que se validó la identidad (mockup Alvarez 51e:
 * "Registro de la validación · Conversación #WA-…"). Nullable: la validación
 * puede dispararse desde el perfil del cliente sin conversación activa.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('helpdesk_conversations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversation_id');
        });
    }
};
