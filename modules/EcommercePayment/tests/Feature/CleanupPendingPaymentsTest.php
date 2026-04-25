<?php

namespace Modules\EcommercePayment\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Tests\TestCase;

class CleanupPendingPaymentsTest extends TestCase
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

    public function test_command_deletes_old_pending_payments(): void
    {
        DB::table('ecommerce_payments')->insert([
            'charge_id' => 'WP_OLD_001',
            'amount' => 100,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => PaymentStatus::PENDING->value,
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
        ]);

        DB::table('ecommerce_payments')->insert([
            'charge_id' => 'WP_NEW_001',
            'amount' => 100,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => PaymentStatus::PENDING->value,
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);

        $this->artisan('ecommerce-payment:cleanup-pending')
            ->assertSuccessful()
            ->expectsOutputToContain('Deleted 1');

        $old = DB::table('ecommerce_payments')->where('charge_id', 'WP_OLD_001')->first();
        $this->assertNotNull($old->deleted_at, 'Old pending payment should be soft deleted');
        $this->assertDatabaseHas('ecommerce_payments', ['charge_id' => 'WP_NEW_001', 'deleted_at' => null]);
    }

    public function test_command_respects_custom_hours_option(): void
    {
        DB::table('ecommerce_payments')->insert([
            'charge_id' => 'WP_002',
            'amount' => 100,
            'currency' => 'COP',
            'payment_channel' => 'wompi',
            'status' => PaymentStatus::PENDING->value,
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        $this->artisan('ecommerce-payment:cleanup-pending', ['--hours' => 6])
            ->assertSuccessful()
            ->expectsOutput('No pending payments to clean up.');

        $this->assertDatabaseHas('ecommerce_payments', ['charge_id' => 'WP_002']);
    }
}
