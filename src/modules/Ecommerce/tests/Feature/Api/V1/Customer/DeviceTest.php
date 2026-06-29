<?php

namespace Modules\Ecommerce\Tests\Feature\Api\V1\Customer;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerPushToken;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_register_device(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $response = $this->postJson('/api/v1/me/devices', [
            'token' => 'fcm-test-token-1234567890',
            'platform' => 'android',
            'device_id' => 'device-abc-123',
            'app_version' => '1.0.0',
            'locale' => 'es',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('ecommerce_customer_push_tokens', [
            'customer_id' => $customer->id,
            'device_id' => 'device-abc-123',
        ]);
    }

    public function test_register_device_is_idempotent_by_device_id(): void
    {
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer, ['*']);

        $payload = [
            'token' => 'fcm-token-original',
            'platform' => 'ios',
            'device_id' => 'iphone-15-1234',
        ];

        $this->postJson('/api/v1/me/devices', $payload);
        $this->postJson('/api/v1/me/devices', [...$payload, 'token' => 'fcm-token-rotated']);

        $this->assertSame(1, CustomerPushToken::query()->where('device_id', 'iphone-15-1234')->count());
        $this->assertSame('fcm-token-rotated', CustomerPushToken::query()->where('device_id', 'iphone-15-1234')->value('token'));
    }

    public function test_unauthenticated_cannot_register(): void
    {
        $response = $this->postJson('/api/v1/me/devices', [
            'token' => 'x',
            'platform' => 'android',
        ]);

        $response->assertUnauthorized();
    }
}
