<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Order;
use Modules\Remarketing\Models\Store;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerTimelineTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Store $store;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_customers')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->user = $this->userWithPermissions(['remarketing.customers.view']);

        $this->store = Store::query()->create([
            'user_id' => $this->user->id,
            'platform' => 'manual',
            'name' => 'Timeline Test Store',
            'domain' => 'https://timeline-test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);

        $this->customer = Customer::query()->create([
            'store_id' => $this->store->id,
            'email' => 'timeline-'.uniqid().'@example.com',
            'email_hash' => hash('sha256', uniqid()),
            'status' => 'subscribed',
            'consent_marketing' => true,
        ]);
    }

    public function test_show_renders_customer_timeline_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer))
            ->assertOk()
            ->assertViewIs('remarketing::customers.show')
            ->assertViewHas('customer', $this->customer);
    }

    public function test_show_passes_timeline_variable_to_view(): void
    {
        $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer))
            ->assertOk()
            ->assertViewHas('timeline');
    }

    public function test_timeline_includes_message_events(): void
    {
        $campaign = $this->createCampaign();
        Message::query()->create([
            'store_id' => $this->store->id,
            'campaign_id' => $campaign->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'subject' => 'Test Subject',
            'status' => 'delivered',
            'is_holdout' => false,
            'open_token' => Str::random(64),
            'click_token' => Str::random(64),
            'sent_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer));

        $timeline = $response->viewData('timeline');
        $kinds = array_column($timeline, 'kind');

        $this->assertContains('message', $kinds);
    }

    public function test_timeline_includes_order_events(): void
    {
        Order::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'external_id' => 'ext-'.uniqid(),
            'order_number' => 'ORD-001',
            'status' => 'completed',
            'total' => 99.90,
            'subtotal' => 90.00,
            'currency' => 'EUR',
            'placed_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer));

        $timeline = $response->viewData('timeline');
        $kinds = array_column($timeline, 'kind');

        $this->assertContains('order', $kinds);
    }

    public function test_timeline_includes_event_entries(): void
    {
        Event::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'type' => 'page_view',
            'properties' => ['url' => '/products/shoes'],
            'occurred_at' => now()->subHours(3),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer));

        $timeline = $response->viewData('timeline');
        $kinds = array_column($timeline, 'kind');

        $this->assertContains('event', $kinds);
    }

    public function test_timeline_includes_consent_events(): void
    {
        ConsentEvent::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'event_type' => 'granted',
            'source' => 'checkout',
            'occurred_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer));

        $timeline = $response->viewData('timeline');
        $kinds = array_column($timeline, 'kind');

        $this->assertContains('consent', $kinds);
    }

    public function test_timeline_is_sorted_by_date_descending(): void
    {
        $campaign = $this->createCampaign();

        Message::query()->create([
            'store_id' => $this->store->id,
            'campaign_id' => $campaign->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'subject' => 'Old email',
            'status' => 'sent',
            'is_holdout' => false,
            'open_token' => Str::random(64),
            'click_token' => Str::random(64),
            'sent_at' => now()->subDays(10),
        ]);

        Order::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'external_id' => 'ext-'.uniqid(),
            'order_number' => 'ORD-RECENT',
            'status' => 'completed',
            'total' => 50.00,
            'subtotal' => 50.00,
            'currency' => 'EUR',
            'placed_at' => now()->subDay(),
        ]);

        $timeline = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer))
            ->viewData('timeline');

        $this->assertCount(2, $timeline);
        $this->assertEquals('order', $timeline[0]['kind'], 'Most recent item should appear first');
        $this->assertEquals('message', $timeline[1]['kind'], 'Older item should appear last');
    }

    public function test_timeline_each_item_has_required_keys(): void
    {
        Order::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'external_id' => 'ext-'.uniqid(),
            'order_number' => 'ORD-KEYS',
            'status' => 'pending',
            'total' => 10.00,
            'subtotal' => 10.00,
            'currency' => 'EUR',
            'placed_at' => now(),
        ]);

        $timeline = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer))
            ->viewData('timeline');

        $this->assertNotEmpty($timeline);

        foreach ($timeline as $item) {
            $this->assertArrayHasKey('kind', $item);
            $this->assertArrayHasKey('date', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('tone', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('subtitle', $item);
        }
    }

    public function test_other_user_cannot_see_customer(): void
    {
        $other = $this->userWithPermissions(['remarketing.customers.view']);

        $this->actingAs($other)
            ->get(route('remarketing.customers.show', $this->customer))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_customer_show(): void
    {
        $this->get(route('remarketing.customers.show', $this->customer))
            ->assertRedirect();
    }

    public function test_consent_tone_is_success_for_granted_event(): void
    {
        ConsentEvent::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'event_type' => 'granted',
            'source' => 'popup',
            'occurred_at' => now(),
        ]);

        $timeline = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer))
            ->viewData('timeline');

        $consentItem = collect($timeline)->firstWhere('kind', 'consent');
        $this->assertEquals('success', $consentItem['tone']);
    }

    public function test_consent_tone_is_danger_for_withdrawn_event(): void
    {
        ConsentEvent::query()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'event_type' => 'withdrawn',
            'source' => 'admin',
            'occurred_at' => now(),
        ]);

        $timeline = $this->actingAs($this->user)
            ->get(route('remarketing.customers.show', $this->customer))
            ->viewData('timeline');

        $consentItem = collect($timeline)->firstWhere('kind', 'consent');
        $this->assertEquals('danger', $consentItem['tone']);
    }

    private function createCampaign(): Campaign
    {
        return Campaign::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Campaign '.uniqid(),
            'subject' => 'Test',
            'from_name' => 'Tester',
            'from_email' => 'sender@example.com',
            'status' => 'sent',
            'settings' => [],
        ]);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
