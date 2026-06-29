<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_shipments', function (Blueprint $table) {
            $table->string('tracking_id')->nullable()->after('note');
            $table->string('tracking_link', 500)->nullable()->after('tracking_id');
            $table->string('shipping_company_name')->nullable()->after('tracking_link');
            $table->date('estimate_date_shipped')->nullable()->after('shipping_company_name');
            $table->date('date_shipped')->nullable()->after('estimate_date_shipped');
            $table->string('delivery_token', 100)->nullable()->unique()->after('date_shipped');
            $table->timestamp('delivered_at')->nullable()->after('delivery_token');
            $table->string('delivered_by', 45)->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_shipments', function (Blueprint $table) {
            $table->dropUnique(['delivery_token']);
            $table->dropColumn([
                'tracking_id',
                'tracking_link',
                'shipping_company_name',
                'estimate_date_shipped',
                'date_shipped',
                'delivery_token',
                'delivered_at',
                'delivered_by',
            ]);
        });
    }
};
