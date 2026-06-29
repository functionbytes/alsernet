<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_order_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable()->after('city');
            $table->unsignedBigInteger('state_id')->nullable()->after('country_id');
            $table->unsignedBigInteger('city_id')->nullable()->after('state_id');

            $table->index('country_id');
            $table->index('state_id');
            $table->index('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_order_addresses', function (Blueprint $table) {
            $table->dropIndex(['country_id']);
            $table->dropIndex(['state_id']);
            $table->dropIndex(['city_id']);
            $table->dropColumn(['country_id', 'state_id', 'city_id']);
        });
    }
};
