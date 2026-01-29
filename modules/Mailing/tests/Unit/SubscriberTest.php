<?php

namespace Modules\Mailing\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailing\Enums\SubscriberStatus;
use Modules\Mailing\Models\MailingGroup;
use Modules\Mailing\Models\Subscriber;
use Tests\TestCase;

class SubscriberTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_subscriber(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertDatabaseHas('mailing_subscribers', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertEquals('test@example.com', $subscriber->email);
    }

    public function test_subscriber_has_default_active_status(): void
    {
        $subscriber = Subscriber::factory()->create();

        $this->assertEquals(SubscriberStatus::ACTIVE, $subscriber->status);
    }

    public function test_can_subscribe_subscriber(): void
    {
        $subscriber = Subscriber::factory()->unsubscribed()->create();

        $subscriber->subscribe();

        $this->assertEquals(SubscriberStatus::ACTIVE, $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->subscribed_at);
        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_can_unsubscribe_subscriber(): void
    {
        $subscriber = Subscriber::factory()->create();

        $subscriber->unsubscribe();

        $this->assertEquals(SubscriberStatus::UNSUBSCRIBED, $subscriber->fresh()->status);
        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_can_mark_subscriber_as_bounced(): void
    {
        $subscriber = Subscriber::factory()->create();

        $subscriber->markAsBounced();

        $this->assertEquals(SubscriberStatus::BOUNCED, $subscriber->fresh()->status);
    }

    public function test_subscriber_metadata_is_cast_to_array(): void
    {
        $metadata = ['source' => 'website', 'ip' => '192.168.1.1'];

        $subscriber = Subscriber::factory()->create([
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($subscriber->metadata);
        $this->assertEquals($metadata, $subscriber->metadata);
    }

    public function test_subscriber_custom_fields_are_cast_to_array(): void
    {
        $customFields = ['company' => 'Acme Inc', 'role' => 'Developer'];

        $subscriber = Subscriber::factory()->create([
            'custom_fields' => $customFields,
        ]);

        $this->assertIsArray($subscriber->custom_fields);
        $this->assertEquals($customFields, $subscriber->custom_fields);
    }

    public function test_subscriber_dates_are_cast_to_datetime(): void
    {
        $subscriber = Subscriber::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscriber->subscribed_at);
        $this->assertNotNull($subscriber->subscribed_at);
    }

    public function test_scope_active_returns_only_active_subscribers(): void
    {
        Subscriber::factory()->count(3)->create(['status' => SubscriberStatus::ACTIVE]);
        Subscriber::factory()->count(2)->unsubscribed()->create();

        $active = Subscriber::active()->get();

        $this->assertCount(3, $active);
        $active->each(function ($subscriber) {
            $this->assertEquals(SubscriberStatus::ACTIVE, $subscriber->status);
        });
    }

    public function test_scope_subscribed_returns_only_subscribed_subscribers(): void
    {
        Subscriber::factory()->count(4)->create();
        Subscriber::factory()->count(1)->pending()->create();

        $subscribed = Subscriber::subscribed()->get();

        $this->assertCount(4, $subscribed);
        $subscribed->each(function ($subscriber) {
            $this->assertEquals(SubscriberStatus::ACTIVE, $subscriber->status);
            $this->assertNotNull($subscriber->subscribed_at);
        });
    }

    public function test_scope_needs_syncing_returns_unsynced_subscribers(): void
    {
        Subscriber::factory()->count(3)->create(['mailrelay_id' => null]);
        Subscriber::factory()->count(2)->synced()->create();

        $needsSyncing = Subscriber::needsSyncing()->get();

        $this->assertGreaterThanOrEqual(3, $needsSyncing->count());
    }

    public function test_scope_synced_returns_only_synced_subscribers(): void
    {
        Subscriber::factory()->count(5)->synced()->create();
        Subscriber::factory()->count(3)->create(['mailrelay_id' => null]);

        $synced = Subscriber::synced()->get();

        $this->assertCount(5, $synced);
        $synced->each(function ($subscriber) {
            $this->assertNotNull($subscriber->mailrelay_id);
        });
    }

    public function test_subscriber_factory_states_work_correctly(): void
    {
        $unsubscribed = Subscriber::factory()->unsubscribed()->create();
        $this->assertEquals(SubscriberStatus::UNSUBSCRIBED, $unsubscribed->status);
        $this->assertNotNull($unsubscribed->unsubscribed_at);

        $bounced = Subscriber::factory()->bounced()->create();
        $this->assertEquals(SubscriberStatus::BOUNCED, $bounced->status);

        $pending = Subscriber::factory()->pending()->create();
        $this->assertEquals(SubscriberStatus::PENDING, $pending->status);
        $this->assertNull($pending->subscribed_at);

        $banned = Subscriber::factory()->banned()->create();
        $this->assertEquals(SubscriberStatus::BANNED, $banned->status);

        $synced = Subscriber::factory()->synced()->create();
        $this->assertNotNull($synced->mailrelay_id);
        $this->assertNotNull($synced->last_synced_at);
    }

    public function test_subscriber_has_groups_relationship(): void
    {
        $subscriber = Subscriber::factory()->create();
        $groups = MailingGroup::factory()->count(2)->create();

        $subscriber->groups()->attach($groups->pluck('id'));

        $this->assertCount(2, $subscriber->groups);
        $this->assertInstanceOf(MailingGroup::class, $subscriber->groups->first());
    }

    public function test_can_create_subscriber_with_metadata(): void
    {
        $subscriber = Subscriber::factory()->withMetadata()->create();

        $this->assertIsArray($subscriber->metadata);
        $this->assertArrayHasKey('source', $subscriber->metadata);
        $this->assertArrayHasKey('ip', $subscriber->metadata);
    }

    public function test_subscriber_email_must_be_unique(): void
    {
        Subscriber::factory()->create(['email' => 'unique@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Subscriber::factory()->create(['email' => 'unique@example.com']);
    }
}
