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
use Tests\TestCase;

class PreferencesControllerTest extends TestCase
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
            'domain' => 'https://test-prefs.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ]);

        $this->customer = Customer::query()->create([
            'store_id' => $this->store->id,
            'email' => 'prefs-'.uniqid().'@example.com',
            'status' => 'subscribed',
            'consent_marketing' => 1,
            'email_hash' => hash('sha256', 'prefs@example.com'),
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

    public function test_show_renders_preferences_view_with_known_token(): void
    {
        $message = $this->createMessage();

        $this->get(route('remarketing.public.preferences.show', $message->open_token))
            ->assertOk()
            ->assertViewIs('remarketing::public.preferences')
            ->assertViewHas('customer', $this->customer);
    }

    public function test_show_with_unknown_token_renders_view_with_null_customer(): void
    {
        $this->get(route('remarketing.public.preferences.show', 'invalid-token'))
            ->assertOk()
            ->assertViewIs('remarketing::public.preferences')
            ->assertViewHas('customer', null);
    }

    public function test_update_saves_preferences_and_returns_saved_flag(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.preferences.update', $message->open_token), [
            'preferences' => ['newsletters' => true, 'promotions' => false],
        ])
            ->assertOk()
            ->assertViewIs('remarketing::public.preferences')
            ->assertViewHas('saved', true);
    }

    public function test_update_merges_preferences_into_customer_attributes(): void
    {
        $message = $this->createMessage();

        $this->post(route('remarketing.public.preferences.update', $message->open_token), [
            'preferences' => ['newsletters' => true],
        ]);

        $this->customer->refresh();
        $attrs = (array) $this->customer->attributes;
        $this->assertArrayHasKey('preferences', $attrs);
        $this->assertTrue($attrs['preferences']['newsletters']);
    }

    public function test_update_with_invalid_token_does_not_error(): void
    {
        $this->post(route('remarketing.public.preferences.update', 'bad-token'), [
            'preferences' => ['newsletters' => true],
        ])
            ->assertOk()
            ->assertViewHas('saved', true);
    }

    public function test_click_token_also_resolves_customer_for_preferences(): void
    {
        $message = $this->createMessage();

        $this->get(route('remarketing.public.preferences.show', $message->click_token))
            ->assertOk()
            ->assertViewHas('customer', $this->customer);
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
