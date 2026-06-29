<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->string('wishlist_share_token', 60)->nullable()->unique()->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_customers', function (Blueprint $table) {
            $table->dropColumn('wishlist_share_token');
        });
    }
};
