<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_sending_servers', function (Blueprint $table): void {
            // Signing secret del webhook del proveedor (Mailgun HTTP signing key,
            // etc.). Guardado encriptado como el resto de credenciales. Nullable:
            // los webhooks siguen funcionando con el gate del serverUid mientras
            // no se configure (verificación fail-open, opt-in por servidor).
            $table->text('webhook_signing_secret')->nullable()->after('api_secret_key');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_sending_servers', function (Blueprint $table): void {
            $table->dropColumn('webhook_signing_secret');
        });
    }
};
