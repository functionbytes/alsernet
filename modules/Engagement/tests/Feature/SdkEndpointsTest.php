<?php

namespace Modules\Engagement\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Engagement\Models\PersonalizationRule;
use Modules\Engagement\Models\TriggerRule;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Tests\TestCase;

class SdkEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private Web $web;

    private Inbox $inbox;

    private string $websiteToken;

    /**
     * Configure the 'helpdesk' database connection before migrations run.
     */
    protected function beforeRefreshingDatabase(): void
    {
        if (! config()->has('database.connections.helpdesk')) {
            config()->set('database.connections.helpdesk', config('database.connections.sqlite'));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->web = Web::create([
            'website_url' => 'https://example.com',
            'widget_color' => '#90bb13',
            'widget_position' => 'right',
            'welcome_title' => 'Hi there!',
            'welcome_tagline' => 'How can we help?',
        ]);

        $this->websiteToken = $this->web->website_token;

        $this->inbox = Inbox::create([
            'name' => 'Test Inbox',
            'channel_type' => 'web',
            'channel_id' => $this->web->id,
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function sdkToken(): array
    {
        return ['X-Website-Token' => $this->websiteToken];
    }

    private function validInitPayload(): array
    {
        return [
            'visitorId' => Str::random(32),
            'page' => [
                'url' => 'https://example.com/products',
                'title' => 'Products',
            ],
            'device' => [
                'userAgent' => 'Mozilla/5.0',
                'language' => 'en',
                'timezone' => 'UTC',
                'viewport' => ['width' => 1280, 'height' => 800],
            ],
        ];
    }

    private function callInit(): string
    {
        $response = $this->postJson(
            route('engagement.sdk.init'),
            $this->validInitPayload(),
            $this->sdkToken(),
        );

        return $response->json('data.sessionToken');
    }

    // -------------------------------------------------------------------------
    // 1. POST /hd/api/sdk/init
    // -------------------------------------------------------------------------

    public function test_init_returns_401_without_token(): void
    {
        $this->postJson(route('engagement.sdk.init'), $this->validInitPayload())
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_init_returns_401_with_invalid_token(): void
    {
        $this->postJson(
            route('engagement.sdk.init'),
            $this->validInitPayload(),
            ['X-Website-Token' => 'totally-invalid-token'],
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_init_returns_200_with_valid_token_and_body(): void
    {
        $this->postJson(
            route('engagement.sdk.init'),
            $this->validInitPayload(),
            $this->sdkToken(),
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'sessionToken',
                    'score',
                    'segment',
                    'triggers',
                    'personalizations',
                    'wsChannel',
                ],
            ]);
    }

    public function test_init_returns_session_token_as_ws_channel_suffix(): void
    {
        $response = $this->postJson(
            route('engagement.sdk.init'),
            $this->validInitPayload(),
            $this->sdkToken(),
        )->assertOk();

        $sessionToken = $response->json('data.sessionToken');

        $this->assertSame('widget-session.'.$sessionToken, $response->json('data.wsChannel'));
    }

    public function test_init_reuses_existing_session_token(): void
    {
        $firstToken = $this->callInit();

        $payload = $this->validInitPayload();

        $secondResponse = $this->postJson(
            route('engagement.sdk.init'),
            $payload,
            array_merge($this->sdkToken(), ['X-Session-Token' => $firstToken]),
        )->assertOk();

        $this->assertSame($firstToken, $secondResponse->json('data.sessionToken'));
    }

    public function test_init_inbox_inactive_returns_403(): void
    {
        $this->inbox->update(['is_active' => false]);

        $this->postJson(
            route('engagement.sdk.init'),
            $this->validInitPayload(),
            $this->sdkToken(),
        )->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // 2. POST /hd/api/sdk/identify
    // -------------------------------------------------------------------------

    public function test_identify_returns_401_without_token(): void
    {
        $this->postJson(route('engagement.sdk.identify'), ['email' => 'user@example.com'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_identify_returns_422_without_identifier(): void
    {
        $this->postJson(
            route('engagement.sdk.identify'),
            ['name' => 'Visitor Only'],
            $this->sdkToken(),
        )->assertUnprocessable();
    }

    public function test_identify_returns_customer_id_with_email(): void
    {
        $this->postJson(
            route('engagement.sdk.identify'),
            ['email' => 'customer@example.com'],
            $this->sdkToken(),
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.linked', true)
            ->assertJsonStructure(['data' => ['customerId', 'linked']]);
    }

    public function test_identify_is_idempotent_for_same_email(): void
    {
        $headers = $this->sdkToken();
        $payload = ['email' => 'idempotent@example.com'];

        $firstId = $this->postJson(route('engagement.sdk.identify'), $payload, $headers)
            ->assertOk()
            ->json('data.customerId');

        $secondId = $this->postJson(route('engagement.sdk.identify'), $payload, $headers)
            ->assertOk()
            ->json('data.customerId');

        $this->assertSame($firstId, $secondId);
    }

    // -------------------------------------------------------------------------
    // 3. POST /hd/api/sdk/track
    // -------------------------------------------------------------------------

    public function test_track_returns_401_without_token(): void
    {
        $this->postJson(route('engagement.sdk.track'), ['events' => []])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_track_returns_401_without_session_token(): void
    {
        $this->postJson(
            route('engagement.sdk.track'),
            ['events' => [['name' => 'page_view', 'ts' => now()->toIso8601String()]]],
            $this->sdkToken(),
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_track_accepts_single_event(): void
    {
        $sessionToken = $this->callInit();

        $this->postJson(
            route('engagement.sdk.track'),
            ['events' => [['name' => 'page_view', 'ts' => now()->toIso8601String()]]],
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.accepted', 1);
    }

    public function test_track_returns_422_for_more_than_50_events(): void
    {
        $sessionToken = $this->callInit();

        $events = array_fill(
            0,
            51,
            ['name' => 'click', 'ts' => now()->toIso8601String()],
        );

        $this->postJson(
            route('engagement.sdk.track'),
            ['events' => $events],
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )->assertUnprocessable();
    }

    public function test_track_returns_422_when_events_missing_name(): void
    {
        $sessionToken = $this->callInit();

        $this->postJson(
            route('engagement.sdk.track'),
            ['events' => [['ts' => now()->toIso8601String()]]],
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // 4. POST /hd/api/sdk/context
    // -------------------------------------------------------------------------

    public function test_context_returns_401_without_token(): void
    {
        $this->postJson(route('engagement.sdk.context'), ['context' => ['cartValue' => 50]])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_context_returns_401_without_session_token(): void
    {
        $this->postJson(
            route('engagement.sdk.context'),
            ['context' => ['cartValue' => 50]],
            $this->sdkToken(),
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_context_stores_and_returns_context(): void
    {
        $sessionToken = $this->callInit();

        $this->postJson(
            route('engagement.sdk.context'),
            ['context' => ['cartValue' => 99, 'plan' => 'pro']],
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['context', 'score', 'segment']]);
    }

    public function test_context_returns_422_when_context_missing(): void
    {
        $sessionToken = $this->callInit();

        $this->postJson(
            route('engagement.sdk.context'),
            [],
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )->assertUnprocessable();
    }

    // -------------------------------------------------------------------------
    // 5. GET /hd/api/sdk/triggers
    // -------------------------------------------------------------------------

    public function test_triggers_returns_401_without_token(): void
    {
        $this->getJson(route('engagement.sdk.triggers'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_triggers_returns_empty_rules_when_none_exist(): void
    {
        $this->getJson(
            route('engagement.sdk.triggers'),
            $this->sdkToken(),
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rules', []);
    }

    public function test_triggers_returns_only_active_rules(): void
    {
        TriggerRule::factory()->create(['inbox_id' => $this->inbox->id]);
        TriggerRule::factory()->inactive()->create(['inbox_id' => $this->inbox->id]);

        $response = $this->getJson(
            route('engagement.sdk.triggers'),
            $this->sdkToken(),
        )->assertOk();

        $this->assertCount(1, $response->json('data.rules'));
    }

    public function test_triggers_excludes_rules_from_other_inboxes(): void
    {
        $otherInbox = Inbox::create([
            'name' => 'Other Inbox',
            'channel_type' => 'web',
            'channel_id' => 999,
            'is_active' => true,
        ]);

        TriggerRule::factory()->create(['inbox_id' => $otherInbox->id]);

        $response = $this->getJson(
            route('engagement.sdk.triggers'),
            $this->sdkToken(),
        )->assertOk();

        $this->assertCount(0, $response->json('data.rules'));
    }

    // -------------------------------------------------------------------------
    // 6. GET /hd/api/sdk/personalizations
    // -------------------------------------------------------------------------

    public function test_personalizations_returns_401_without_token(): void
    {
        $this->getJson(route('engagement.sdk.personalizations'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_personalizations_returns_empty_rules_when_none_exist(): void
    {
        $this->getJson(
            route('engagement.sdk.personalizations'),
            $this->sdkToken(),
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rules', []);
    }

    public function test_personalizations_returns_only_active_rules(): void
    {
        PersonalizationRule::factory()->create(['inbox_id' => $this->inbox->id]);
        PersonalizationRule::factory()->inactive()->create(['inbox_id' => $this->inbox->id]);

        $response = $this->getJson(
            route('engagement.sdk.personalizations'),
            $this->sdkToken(),
        )->assertOk();

        $this->assertCount(1, $response->json('data.rules'));
    }

    public function test_personalizations_excludes_rules_from_other_inboxes(): void
    {
        $otherInbox = Inbox::create([
            'name' => 'Other Inbox',
            'channel_type' => 'web',
            'channel_id' => 999,
            'is_active' => true,
        ]);

        PersonalizationRule::factory()->create(['inbox_id' => $otherInbox->id]);

        $response = $this->getJson(
            route('engagement.sdk.personalizations'),
            $this->sdkToken(),
        )->assertOk();

        $this->assertCount(0, $response->json('data.rules'));
    }

    // -------------------------------------------------------------------------
    // 7. GET /hd/api/sdk/recommendations
    // -------------------------------------------------------------------------

    public function test_recommendations_returns_401_without_website_token(): void
    {
        $this->getJson(route('engagement.sdk.recommendations'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_recommendations_returns_401_without_session_token(): void
    {
        $this->getJson(
            route('engagement.sdk.recommendations'),
            $this->sdkToken(),
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_recommendations_returns_products_array(): void
    {
        $sessionToken = $this->callInit();

        $this->getJson(
            route('engagement.sdk.recommendations'),
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['products']]);
    }

    public function test_recommendations_respects_limit_parameter(): void
    {
        $sessionToken = $this->callInit();

        $response = $this->getJson(
            route('engagement.sdk.recommendations').'?limit=3',
            array_merge($this->sdkToken(), ['X-Session-Token' => $sessionToken]),
        )->assertOk();

        $products = $response->json('data.products');
        $this->assertIsArray($products);
        $this->assertLessThanOrEqual(3, count($products));
    }
}
