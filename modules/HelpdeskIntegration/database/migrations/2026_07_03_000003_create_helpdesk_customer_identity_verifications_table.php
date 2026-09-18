<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codigos OTP para verificar la identidad del cliente antes de exponer sus
 * integraciones externas. `expires_at` acota la ventana para introducir el
 * codigo (10 min); `verified_at` es el inicio de la sesion verificada (TTL
 * de 30 min aplicado en CustomerIdentityVerificationService::isVerified()).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('helpdesk_customers')->cascadeOnDelete();
            $table->enum('channel', ['email', 'sms']);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'verified_at'], 'hciv_customer_verified_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_customer_identity_verifications');
    }
};
