<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            if (! Schema::hasColumn('ecommerce_products', 'is_subscription')) {
                $table->boolean('is_subscription')->default(false)->after('with_storehouse_management');
            }

            if (! Schema::hasColumn('ecommerce_products', 'subscription_interval')) {
                $table->string('subscription_interval', 20)->nullable()->after('is_subscription');
            }

            if (! Schema::hasColumn('ecommerce_products', 'subscription_discount_percent')) {
                $table->decimal('subscription_discount_percent', 5, 2)->default(0)->after('subscription_interval');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            $table->dropColumn([
                'is_subscription',
                'subscription_interval',
                'subscription_discount_percent',
            ]);
        });
    }
};
