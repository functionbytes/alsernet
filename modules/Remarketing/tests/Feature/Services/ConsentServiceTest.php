<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;
use Modules\Remarketing\Services\ConsentService;
use Tests\TestCase;

class ConsentServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ConsentService $service;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->service = new ConsentService;
        $this->store = $this->createStore();
    }

    public function test_grants_consent_creates_consent_event(): void
    {
        Queue::fake();
        $customer = $this->createCustomer(['country' => 'US']);

        $this->service->grantConsent($customer, 'checkout');

        $this->assertDatabaseHas('remarketing_consent_events', [
            'customer_id' => $customer->id,
            'event_type' => 'granted',
            'source' => 'checkout',
        ]);
    }

    public function test_grants_consent_with_geofence_keeps_pending_status(): void
    {
        Queue::fake();
        $customer = $this->createCustomer(['country' => 'ES', 'status' => 'pending']);

        $this->service->grantConsent($customer, 'form');

        $customer->refresh();
        $this->assertEquals('pending', $customer->status);
        $this->assertDatabaseHas('remarketing_consent_events', [
            'customer_id' => $customer->id,
            'event_type' => 'granted',
        ]);
    }

    public function test_grants_consent_without_geofence_marks_subscribed(): void
    {
        Queue::fake();
        $customer = $this->createCustomer(['country' => 'US', 'status' => 'pending']);

        $this->service->grantConsent($customer, 'checkout');

        $customer->refresh();
        $this->assertEquals('subscribed', $customer->status);
        $this->assertTrue($customer->consent_marketing);
    }

    public function test_confirm_double_optin_marks_subscribed_and_creates_event(): void
    {
        $token = hash_hmac('sha256', 'test-token-data', config('app.key'));
        $customer = $this->createCustomer([
            'status' => 'pending',
            'double_optin_token' => $token,
            'double_optin_sent_at' => now()->subHours(1),
        ]);

        $result = $this->service->confirmDoubleOptin($token);

        $this->assertNotNull($result);
        $this->assertEquals('subscribed', $result->status);
        $this->assertTrue($result->consent_marketing);
        $this->assertDatabaseHas('remarketing_consent_events', [
            'customer_id' => $customer->id,
            'event_type' => 'confirmed',
            'source' => 'double_optin_confirm',
        ]);
    }

    public function test_confirm_double_optin_returns_null_for_invalid_token(): void
    {
        $result = $this->service->confirmDoubleOptin('invalid-token-xyz-9999');

        $this->assertNull($result);
    }

    public function test_withdraw_marks_unsubscribed_and_creates_suppression(): void
    {
        $customer = $this->createCustomer([
            'status' => 'subscribed',
            'consent_marketing' => true,
        ]);

        $this->service->withdraw($customer, 'admin');

        $customer->refresh();
        $this->assertEquals('unsubscribed', $customer->status);
        $this->assertFalse($customer->consent_marketing);
        $this->assertDatabaseHas('remarketing_suppressions', [
            'store_id' => $this->store->id,
            'email' => $customer->email,
        ]);
        $this->assertDatabaseHas('remarketing_consent_events', [
            'customer_id' => $customer->id,
            'event_type' => 'withdrawn',
        ]);
    }

    public function test_is_geofenced_returns_true_for_eu_countries(): void
    {
        foreach (['ES', 'FR', 'DE', 'IT', 'PT', 'NL', 'BE', 'PL'] as $country) {
            $this->assertTrue(
                $this->service->isGeofenced($country),
                "Expected {$country} to be geofenced"
            );
        }
    }

    public function test_is_geofenced_returns_true_for_brazil(): void
    {
        $this->assertTrue($this->service->isGeofenced('BR'));
    }

    public function test_is_geofenced_returns_false_for_us_or_unknown(): void
    {
        $this->assertFalse($this->service->isGeofenced('US'));
        $this->assertFalse($this->service->isGeofenced('MX'));
        $this->assertFalse($this->service->isGeofenced(null));
        $this->assertFalse($this->service->isGeofenced(''));
    }

    public function test_is_suppressed_returns_true_when_email_in_suppression_list(): void
    {
        $email = 'suppressed-'.uniqid().'@example.com';
        Suppression::query()->create([
            'store_id' => $this->store->id,
            'email' => $email,
            'reason' => 'bounce',
        ]);

        $this->assertTrue($this->service->isSuppressed($this->store, $email));
        $this->assertFalse($this->service->isSuppressed($this->store, 'not-suppressed@example.com'));
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => User::query()->first()?->id ?? 1,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
        ], $attributes));
    }

    private function createCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'store_id' => $this->store->id,
            'email' => 'customer-'.uniqid().'@example.com',
            'email_hash' => hash('sha256', 'customer@example.com'),
            'status' => 'pending',
            'consent_marketing' => false,
        ], $attributes));
    }
}
