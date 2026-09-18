<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contador de intentos fallidos persistido POR CÓDIGO. El rate limiter en
 * cache (AuthRateLimiter) se reinicia con su decay y se diluye rotando IP;
 * este contador vive en la fila del código: al agotar los intentos el código
 * se quema (expires_at pasado) y solo queda pedir uno nuevo, que a su vez
 * está limitado por el scope customer_identity_request.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('code_hash');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customer_identity_verifications', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
