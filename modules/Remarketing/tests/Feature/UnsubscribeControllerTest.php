<?php

namespace Modules\Remarketing\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;
use Tests\TestCase;

class UnsubscribeControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;

    private Customer $customer;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_messages')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $user = User::factory()->create();

        $this->store = Store::query()->create([
            'user_id' => $user->id,
            'platform' => 'manual',
            'name' => 'Test Store',
            'domain' => 'https://test-unsub.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);

        $this->customer = Customer::query()->create([
            'store_id' => $this->store->id,
            'email' => 'unsub-test-'.uniqid().'@example.com',
            'status' => 'subscribed',
            'consent_marketing' => 1,
            'email_hash' => hash('sha256', 'unsub-test@example.com'),
        ]);

        $this->campaign = Campaign::query()->create([
            'store_id' => $this->store->id,
            'name' => 'Test Campaign',
            'subject' => 'Hello',
            'from_name' => 'Tester',
            'from_email' => 'sender@example.com',
            'status' => 'sending',
            'settings' => [],
        ]);
    }

    // ─── GET: show unsubscribe confirmation ───────────────────────────────────

    public function test_get_with_valid_token_shows_confirmation_view(): void
    {
        $message = $this->createMessage();

        $this->get(route('remarketing.public.unsubscribe', $message->open_token))
            ->assertOk()
            ->assertViewIs('remarketing::public.unsubscribed');
    }

    public function test_get_with_unknown_token_shows_view_without_email(): void
    {
        $this->get(route('remarketing.public.unsubscribe', 'totally-invalid-token'))
            ->assertOk()
            ->assertViewIs('remarketing::public.unsubscribed');
    }

    // ─── POST: process unsubscribe ────────────────────────────────────────────

    public function test_post_unsubscribes_customer_and_returns_ok(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.unsubscribe', $message->open_token))
            ->assertOk();

        $this->customer->refresh();
        $this->assertEquals('unsubscribed', $this->customer->status);
        $this->assertEquals(0, (int) $this->customer->consent_marketing);
        $this->assertNotNull($this->customer->unsubscribed_at);
    }

    public function test_post_adds_email_to_suppressions(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.unsubscribe', $message->open_token));

        $this->assertDatabaseHas('remarketing_suppressions', [
            'store_id' => $this->store->id,
            'email' => $this->customer->email,
            'reason' => 'unsubscribe',
        ]);
    }

    public function test_post_records_consent_withdrawn_event(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.unsubscribe', $message->open_token));

        $this->assertDatabaseHas('remarketing_consent_events', [
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'event_type' => 'withdrawn',
        ]);
    }

    public function test_post_with_invalid_token_returns_ok_silently(): void
    {
        $this->post(route('remarketing.public.unsubscribe', 'bad-token'))
            ->assertOk();
    }

    public function test_post_is_idempotent_does_not_duplicate_suppression(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.unsubscribe', $message->open_token));
        $this->post(route('remarketing.public.unsubscribe', $message->open_token));

        $count = Suppression::query()
            ->where('store_id', $this->store->id)
            ->where('email', $this->customer->email)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_click_token_also_works_for_unsubscribe(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.unsubscribe', $message->click_token))
            ->assertOk();

        $this->customer->refresh();
        $this->assertEquals('unsubscribed', $this->customer->status);
    }

    private function createMessage(array $attributes = []): Message
    {
        return Message::query()->create(array_merge([
            'store_id' => $this->store->id,
            'campaign_id' => $this->campaign->id,
            'customer_id' => $this->customer->id,
            'email' => $this->customer->email,
            'subject' => 'Test',
            'status' => 'sent',
            'is_holdout' => false,
            'open_token' => Str::random(64),
            'click_token' => Str::random(64),
        ], $attributes));
    }
}
