<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->string('provider', 50)->nullable()->after('email_verification_token');
            $table->string('provider_id', 191)->nullable()->after('provider');
            $table->index(['provider', 'provider_id'], 'ecommerce_customers_provider_provider_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->dropIndex('ecommerce_customers_provider_provider_id_index');
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
