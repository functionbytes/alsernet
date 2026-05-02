<?php

namespace Modules\Remarketing\Tests\Feature\Services;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\SegmentService;
use Tests\TestCase;

class SegmentServiceTest extends TestCase
{
    use DatabaseTransactions;

    private SegmentService $service;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_stores')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->service = new SegmentService;
        $this->store = $this->createStore();
    }

    public function test_compiles_property_equals_condition(): void
    {
        $matchingCustomer = $this->createCustomer(['country' => 'ES']);
        $this->createCustomer(['country' => 'US']);

        $segment = $this->createSegment([
            'operator' => 'AND',
            'conditions' => [
                ['type' => 'property', 'field' => 'country', 'operator' => 'equals', 'value' => 'ES'],
            ],
        ]);

        $members = $this->service->getMembers($segment)->pluck('id');

        $this->assertContains($matchingCustomer->id, $members);
        $this->assertCount(1, $members);
    }

    public function test_compiles_property_in_condition(): void
    {
        $es = $this->createCustomer(['country' => 'ES']);
        $fr = $this->createCustomer(['country' => 'FR']);
        $this->createCustomer(['country' => 'US']);

        $segment = $this->createSegment([
            'operator' => 'AND',
            'conditions' => [
                ['type' => 'property', 'field' => 'country', 'operator' => 'in', 'value' => ['ES', 'FR']],
            ],
        ]);

        $members = $this->service->getMembers($segment)->pluck('id');

        $this->assertContains($es->id, $members);
        $this->assertContains($fr->id, $members);
        $this->assertCount(2, $members);
    }

    public function test_compiles_rfm_gte_condition(): void
    {
        $highValue = $this->createCustomer(['rfm_monetary' => 500]);
        $this->createCustomer(['rfm_monetary' => 50]);

        $segment = $this->createSegment([
            'operator' => 'AND',
            'conditions' => [
                ['type' => 'rfm', 'field' => 'rfm_monetary', 'operator' => 'gte', 'value' => 200],
            ],
        ]);

        $members = $this->service->getMembers($segment)->pluck('id');

        $this->assertContains($highValue->id, $members);
        $this->assertCount(1, $members);
    }

    public function test_compiles_event_at_least_count_condition(): void
    {
        $activeCustomer = $this->createCustomer();
        $inactiveCustomer = $this->createCustomer();

        // Active customer has 3 placed_order events
        for ($i = 0; $i < 3; $i++) {
            Event::query()->create([
                'store_id' => $this->store->id,
                'customer_id' => $activeCustomer->id,
                'type' => 'placed_order',
                'occurred_at' => now(),
                'received_at' => now(),
            ]);
        }

        $segment = $this->createSegment([
            'operator' => 'AND',
            'conditions' => [
                ['type' => 'event', 'event' => 'placed_order', 'operator' => 'at_least', 'count' => 2],
            ],
        ]);

        $members = $this->service->getMembers($segment)->pluck('id');

        $this->assertContains($activeCustomer->id, $members);
        $this->assertNotContains($inactiveCustomer->id, $members);
    }

    public function test_compiles_or_group_combines_conditions(): void
    {
        $esCustomer = $this->createCustomer(['country' => 'ES']);
        $frCustomer = $this->createCustomer(['country' => 'FR']);
        $this->createCustomer(['country' => 'US']);

        $segment = $this->createSegment([
            'operator' => 'OR',
            'conditions' => [
                ['type' => 'property', 'field' => 'country', 'operator' => 'equals', 'value' => 'ES'],
                ['type' => 'property', 'field' => 'country', 'operator' => 'equals', 'value' => 'FR'],
            ],
        ]);

        $members = $this->service->getMembers($segment)->pluck('id');

        $this->assertContains($esCustomer->id, $members);
        $this->assertContains($frCustomer->id, $members);
        $this->assertCount(2, $members);
    }

    public function test_recalculate_updates_member_count(): void
    {
        $this->createCustomer(['country' => 'DE']);
        $this->createCustomer(['country' => 'DE']);
        $this->createCustomer(['country' => 'US']);

        $segment = $this->createSegment([
            'operator' => 'AND',
            'conditions' => [
                ['type' => 'property', 'field' => 'country', 'operator' => 'equals', 'value' => 'DE'],
            ],
        ]);

        $count = $this->service->recalculate($segment);

        $this->assertEquals(2, $count);
        $segment->refresh();
        $this->assertEquals(2, $segment->member_count);
        $this->assertNotNull($segment->last_calculated_at);
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
            'email_hash' => hash('sha256', uniqid()),
            'status' => 'subscribed',
            'consent_marketing' => true,
        ], $attributes));
    }

    private function createSegment(array $conditions): Segment
    {
        return Segment::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Segment '.uniqid(),
            'type' => 'dynamic',
            'conditions' => $conditions,
            'member_count' => 0,
        ]);
    }
}
