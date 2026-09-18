<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerPushToken;
use Modules\Notification\Channels\FcmCustomerChannel;
use Modules\Notification\Enums\PushResult;
use Modules\Notification\Services\PushNotificationService;
use Modules\Notification\Tests\TestCase;

class FcmCustomerChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('ecommerce_customers')) {
            $this->markTestSkipped('Módulo Ecommerce no disponible en el entorno de test.');
        }
    }

    private function notification(): Notification
    {
        return new class extends Notification
        {
            public function toFcm(mixed $notifiable): array
            {
                return ['title' => 'Hola', 'body' => 'Mundo'];
            }
        };
    }

    public function test_invalid_token_is_deactivated(): void
    {
        $customer = Customer::factory()->create();
        $token = CustomerPushToken::factory()->create([
            'customer_id' => $customer->id,
            'is_active' => true,
        ]);

        $push = $this->createMock(PushNotificationService::class);
        $push->method('sendToToken')->willReturn(PushResult::InvalidToken);

        (new FcmCustomerChannel($push))->send($customer, $this->notification());

        $this->assertFalse($token->fresh()->is_active);
    }

    public function test_success_updates_last_used_at(): void
    {
        $customer = Customer::factory()->create();
        $token = CustomerPushToken::factory()->create([
            'customer_id' => $customer->id,
            'is_active' => true,
            'last_used_at' => now()->subWeek(),
        ]);

        $push = $this->createMock(PushNotificationService::class);
        $push->method('sendToToken')->willReturn(PushResult::Success);

        (new FcmCustomerChannel($push))->send($customer, $this->notification());

        $this->assertTrue($token->fresh()->last_used_at->greaterThan(now()->subDay()));
        $this->assertTrue($token->fresh()->is_active);
    }
}
