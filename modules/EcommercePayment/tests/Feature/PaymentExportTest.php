<?php

namespace Modules\EcommercePayment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Exports\PaymentsExport;
use Tests\TestCase;

class PaymentExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('ecommerce_payments', 'deleted_at')) {
            Schema::table('ecommerce_payments', function ($table) {
                $table->softDeletes();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::table('ecommerce_payments')->delete();

        parent::tearDown();
    }

    public function test_export_contains_payment_data(): void
    {
        DB::table('ecommerce_payments')->insert([
            'charge_id' => 'WP_TEST_001',
            'amount' => 150.00,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => PaymentStatus::COMPLETED->value,
            'payment_fee' => 5.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = new PaymentsExport;
        $collection = $export->query()->get();

        $this->assertCount(1, $collection);
        $this->assertEquals('WP_TEST_001', $collection->first()->charge_id);
    }

    public function test_export_filters_by_status(): void
    {
        DB::table('ecommerce_payments')->insert([
            'charge_id' => 'WP_PENDING',
            'amount' => 100,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => PaymentStatus::PENDING->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ecommerce_payments')->insert([
            'charge_id' => 'WP_COMPLETED',
            'amount' => 200,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => PaymentStatus::COMPLETED->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = new PaymentsExport(['status' => PaymentStatus::COMPLETED->value]);
        $collection = $export->query()->get();

        $this->assertCount(1, $collection);
        $this->assertEquals('WP_COMPLETED', $collection->first()->charge_id);
    }

    public function test_export_headings_are_correct(): void
    {
        $export = new PaymentsExport;
        $headings = $export->headings();

        $this->assertEquals([
            'ID', 'Referencia', 'Orden', 'Cliente', 'Monto', 'Comision', 'Moneda', 'Canal', 'Estado', 'Fecha',
        ], $headings);
    }
}
