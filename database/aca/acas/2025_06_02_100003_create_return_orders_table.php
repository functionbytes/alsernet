<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_missing_components')->default(false)->after('status');
            $table->boolean('allows_partial_shipment')->default(true)->after('has_missing_components');
            $table->string('component_status')->default('complete')->after('allows_partial_shipment'); // complete, partial, missing, backorder
            $table->decimal('total_component_deductions', 10, 2)->default(0.00)->after('component_status');
            $table->integer('total_shipments')->default(0)->after('total_component_deductions');
            $table->date('expected_completion_date')->nullable()->after('total_shipments');
            $table->json('component_summary')->nullable()->after('expected_completion_date'); // Resumen de componentes
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'has_missing_components',
                'allows_partial_shipment',
                'component_status',
                'total_component_deductions',
                'total_shipments',
                'expected_completion_date',
                'component_summary',
            ]);
        });
    }
};
