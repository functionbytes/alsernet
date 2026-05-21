<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\ProfileService;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use DatabaseTransactions;

    private ProfileService $service;

    private Store $store;

    private ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->testUser = User::factory()->create();
        $this->service = new ProfileService;
        $this->store = $this->createStore();
    }

    public function test_find_or_create_by_email_creates_new_customer_with_lowercase_email(): void
    {
        $email = 'Test.User-'.uniqid().'@Example.COM';

        $customer = $this->service->findOrCreateByEmail($this->store, $email);

        $this->assertEquals(strtolower(trim($email)), $customer->email);
        $this->assertEquals($this->store->id, $customer->store_id);
        $this->assertDatabaseHas('remarketing_customers', [
            'store_id' => $this->store->id,
            'email' => strtolower(trim($email)),
        ]);
    }

    public function test_find_or_create_by_email_returns_existing_customer_without_duplicating(): void
    {
        $email = 'duplicate-'.uniqid().'@example.com';
        $existing = Customer::query()->create([
            'store_id' => $this->store->id,
            'email' => $email,
            'email_hash' => hash('sha256', $email),
            'status' => 'subscribed',
            'consent_marketing' => true,
        ]);

        $result = $this->service->findOrCreateByEmail($this->store, strtoupper($email));

        $this->assertEquals($existing->id, $result->id);
        $this->assertEquals(1, Customer::query()
            ->where('store_id', $this->store->id)
            ->where('email', $email)
            ->count());
    }

    public function test_find_or_create_by_email_computes_email_hash(): void
    {
        $email = 'hash-test-'.uniqid().'@example.com';

        $customer = $this->service->findOrCreateByEmail($this->store, $email);

        $this->assertEquals(hash('sha256', $email), $customer->email_hash);
    }

    public function test_merge_visitor_to_customer_links_anonymous_events(): void
    {
        $visitorId = 'visitor-'.uniqid();
        $customer = Customer::query()->create([
            'store_id' => $this->store->id,
            'email' => 'merge-'.uniqid().'@example.com',
            'email_hash' => hash('sha256', 'merge@example.com'),
            'status' => 'pending',
            'consent_marketing' => false,
        ]);

        // Create anonymous events with visitor_id and no customer_id
        $event1 = Event::query()->create([
            'store_id' => $this->store->id,
            'visitor_id' => $visitorId,
            'customer_id' => null,
            'type' => 'page_view',
            'occurred_at' => now(),
            'received_at' => now(),
        ]);
        $event2 = Event::query()->create([
            'store_id' => $this->store->id,
            'visitor_id' => $visitorId,
            'customer_id' => null,
            'type' => 'add_to_cart',
            'occurred_at' => now(),
            'received_at' => now(),
        ]);

        // Create event belonging to a different visitor (should not be touched)
        $otherEvent = Event::query()->create([
            'store_id' => $this->store->id,
            'visitor_id' => 'other-visitor-'.uniqid(),
            'customer_id' => null,
            'type' => 'page_view',
            'occurred_at' => now(),
            'received_at' => now(),
        ]);

        $this->service->mergeVisitorToCustomer($visitorId, $customer);

        $this->assertEquals($customer->id, $event1->fresh()->customer_id);
        $this->assertEquals($customer->id, $event2->fresh()->customer_id);
        $this->assertNull($otherEvent->fresh()->customer_id);
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => $this->testUser?->id ?? User::factory()->create()->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ], $attributes));
    }
}
