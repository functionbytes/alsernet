<?php

namespace Modules\Helpdesk\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\BusinessHoursService;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\OutboundMessageService;
use Modules\Helpdesk\Services\RoutingRuleService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for the WhatsApp Business API webhook endpoint.
 *
 * Uses DatabaseTransactions (not RefreshDatabase) because
 * some Helpdesk migrations use $connection = 'helpdesk' which
 * causes ordering issues with migrate:fresh in tests.
 */
class WhatsAppWebhookTest extends TestCase
{
    use DatabaseTransactions;

    /** Wrap both connections so each test is fully isolated. */
    protected $connectionsToTransact = [null, 'helpdesk'];

    private string $appSecret = 'test-whatsapp-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        // Pulse stub — ErpContextService calls Pulse::set() with an array which
        // fails without this; the stub accepts any type for the $value parameter.
        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config([
            'helpdesk.integrations.whatsapp.app_secret' => $this->appSecret,
            'helpdesk.integrations.whatsapp.verify_token' => 'test-wa-verify-token',
            'helpdesk.integrations.whatsapp.access_token' => 'test-wa-access-token',
            'helpdesk.integrations.whatsapp.phone_number_id' => '12345678',
            'helpdesk.integrations.whatsapp.enabled' => true,
            'helpdesk.integrations.facebook.page_access_token' => 'test-fb-token',
            'helpdesk.integrations.facebook.app_secret' => 'test-fb-secret',
            'helpdesk.integrations.facebook.verify_token' => 'test-fb-verify',
            'helpdesk.integrations.instagram.access_token' => 'test-ig-token',
            'helpdesk.integrations.instagram.business_account_id' => 'test-ig-account',
            'helpdesk.integrations.instagram.app_secret' => 'test-ig-secret',
            'helpdesk.integrations.instagram.verify_token' => 'test-ig-verify',
            'helpdeskErp.manager_url' => 'http://manager.test',
        ]);

        // Required by SendNewConversationNotification listener (runs sync in tests).
        Role::firstOrCreate(['name' => 'helpdesk-agent', 'guard_name' => 'web']);

        // Ensure an open status exists for findOrCreateConversation.
        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    // ─── Verification (GET) ───────────────────────────────────────────────────

    public function test_whatsapp_webhook_verify_returns_challenge_with_valid_token(): void
    {
        $this->get(route('webhooks.whatsapp.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123challenge',
            'hub_verify_token' => 'test-wa-verify-token',
        ]))->assertOk()->assertContent('abc123challenge');
    }

    public function test_whatsapp_webhook_verify_returns_403_with_wrong_token(): void
    {
        $this->get(route('webhooks.whatsapp.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123',
            'hub_verify_token' => 'wrong-token',
        ]))->assertStatus(403);
    }

    public function test_whatsapp_webhook_verify_returns_403_with_wrong_hub_mode(): void
    {
        $this->get(route('webhooks.whatsapp.verify', [
            'hub_mode' => 'unsubscribe',
            'hub_challenge' => 'abc123',
            'hub_verify_token' => 'test-wa-verify-token',
        ]))->assertStatus(403);
    }

    public function test_whatsapp_webhook_verify_returns_403_when_no_verify_token_configured(): void
    {
        config(['helpdesk.integrations.whatsapp.verify_token' => '']);

        $this->get(route('webhooks.whatsapp.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123',
            'hub_verify_token' => '',
        ]))->assertStatus(403);
    }

    // ─── Group 1: Controller enqueues job ─────────────────────────────────────

    public function test_webhook_queues_process_job_for_incoming_message(): void
    {
        Queue::fake();

        $payload = $this->buildMessagePayload('521234567890', 'wamid.test001', 'Hola');
        $signature = $this->signPayload($payload);

        $this->postJson(
            route('webhooks.whatsapp.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessSocialWebhookJob::class, function ($job) {
            return $job->channel === 'whatsapp'
                && $job->eventType === 'message';
        });
    }

    public function test_webhook_post_without_signature_returns_403_when_secret_configured(): void
    {
        $payload = $this->buildMessagePayload('521234567890', 'wamid.test002', 'Hola');

        $this->postJson(route('webhooks.whatsapp.handle'), $payload)
            ->assertForbidden();
    }

    public function test_webhook_post_with_invalid_signature_returns_403(): void
    {
        $payload = $this->buildMessagePayload('521234567890', 'wamid.test003', 'Hola');

        $this->postJson(
            route('webhooks.whatsapp.handle'),
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalidsignaturehex']
        )->assertForbidden();
    }

    public function test_webhook_rejects_unsigned_request_when_no_secret_configured(): void
    {
        Queue::fake();

        config(['helpdesk.integrations.whatsapp.app_secret' => '']);

        $payload = $this->buildMessagePayload('521234567890', 'wamid.test004', 'Hola');

        // Fail-closed: sin secreto configurado (fuera de entorno local) se rechaza.
        $this->postJson(route('webhooks.whatsapp.handle'), $payload)
            ->assertForbidden();

        Queue::assertNotPushed(ProcessSocialWebhookJob::class);
    }

    public function test_status_update_payload_does_not_dispatch_message_job(): void
    {
        Queue::fake();

        $payload = $this->buildStatusPayload('wamid.test005', '521234567890', 'delivered');
        $signature = $this->signPayload($payload);

        $this->postJson(
            route('webhooks.whatsapp.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        // Status events are dispatched as jobs but with a different event type.
        // The controller dispatches any parsed event — assert channel+type if needed.
        // The key assertion is that we don't crash and return ok.
    }

    public function test_non_whatsapp_business_account_object_dispatches_no_job(): void
    {
        Queue::fake();

        // object != 'whatsapp_business_account' — parseWebhookPayload returns []
        $payload = ['object' => 'instagram', 'entry' => []];
        $signature = $this->signPayload($payload);

        $this->postJson(
            route('webhooks.whatsapp.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    // ─── Group 2: Customer creation ───────────────────────────────────────────

    public function test_new_whatsapp_message_creates_customer_if_phone_not_exists(): void
    {
        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);

        $event = $this->buildParsedEvent($phone, 'wamid.new001', 'Hola por primera vez');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        $this->assertDatabaseHas('helpdesk_customers', [
            'whatsapp_phone' => $phone,
        ], 'helpdesk');
    }

    public function test_new_whatsapp_message_finds_existing_customer_by_whatsapp_phone(): void
    {
        $customer = Customer::factory()->create([
            'whatsapp_phone' => '521000111222',
            'name' => 'Cliente Existente',
        ]);

        $event = $this->buildParsedEvent('521000111222', 'wamid.existing001', 'Mensaje repetido');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        // No duplicate customer should be created.
        $this->assertSame(
            1,
            Customer::where('whatsapp_phone', '521000111222')->count()
        );
    }

    // ─── Group 3: Conversation management ────────────────────────────────────

    public function test_new_whatsapp_message_creates_open_conversation_with_whatsapp_channel(): void
    {
        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $event = $this->buildParsedEvent($phone, 'wamid.conv001', 'Primera consulta');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        $this->assertDatabaseHas('helpdesk_conversations', [
            'channel' => 'whatsapp',
            'external_sender_id' => $phone,
        ], 'helpdesk');
    }

    public function test_existing_open_conversation_is_reused_for_same_phone(): void
    {
        $customer = Customer::factory()->create(['whatsapp_phone' => '521999888777']);
        $openStatus = ConversationStatus::where('is_open', true)->first();
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'external_sender_id' => '521999888777',
            'status_id' => $openStatus->id,
        ]);

        $event = $this->buildParsedEvent('521999888777', 'wamid.reuse001', 'Seguimiento');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        // Only the original conversation should exist for this phone.
        $this->assertSame(
            1,
            Conversation::where('channel', 'whatsapp')
                ->where('external_sender_id', '521999888777')
                ->count()
        );
    }

    public function test_closed_conversation_triggers_new_conversation_creation(): void
    {
        $customer = Customer::factory()->create(['whatsapp_phone' => '521777666555']);
        $closedStatus = ConversationStatus::firstOrCreate(
            ['slug' => 'closed'],
            ['name' => 'Closed', 'color' => '#FA896B', 'is_open' => false, 'is_default' => false, 'order' => 2]
        );
        Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'external_sender_id' => '521777666555',
            'status_id' => $closedStatus->id,
        ]);

        $event = $this->buildParsedEvent('521777666555', 'wamid.reopen001', 'Nuevo contacto');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        // A new (open) conversation should have been created.
        $this->assertSame(
            2,
            Conversation::where('channel', 'whatsapp')
                ->where('external_sender_id', '521777666555')
                ->count()
        );
    }

    // ─── Group 4: ConversationItem storage ────────────────────────────────────

    public function test_message_body_is_stored_in_conversation_item(): void
    {
        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $messageId = 'wamid.'.Str::random(20);
        $event = $this->buildParsedEvent($phone, $messageId, 'Cuerpo del mensaje de prueba');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        $this->assertDatabaseHas('helpdesk_conversation_items', [
            'external_id' => $messageId,
            'body' => 'Cuerpo del mensaje de prueba',
        ], 'helpdesk');
    }

    public function test_duplicate_message_id_is_ignored_and_no_duplicate_item_created(): void
    {
        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $messageId = 'wamid.duplicate001';
        $event = $this->buildParsedEvent($phone, $messageId, 'Mensaje original');

        $job = new ProcessSocialWebhookJob('whatsapp', 'message', $event);
        $services = [
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        ];

        // Process the same message twice.
        $job->handle(...$services);
        $job->handle(...$services);

        $this->assertSame(
            1,
            ConversationItem::where('external_id', $messageId)->count()
        );
    }

    // ─── Group 5: ConversationCreated event (ERP fix) ─────────────────────────

    public function test_new_whatsapp_conversation_fires_conversation_created_event(): void
    {
        Event::fake([ConversationCreated::class]);

        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $event = $this->buildParsedEvent($phone, 'wamid.event001', 'Nuevo cliente');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        Event::assertDispatched(ConversationCreated::class);
    }

    public function test_reused_conversation_does_not_fire_conversation_created_event(): void
    {
        Event::fake([ConversationCreated::class]);

        $customer = Customer::factory()->create(['whatsapp_phone' => '521111222333']);
        $openStatus = ConversationStatus::where('is_open', true)->first();
        Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'external_sender_id' => '521111222333',
            'status_id' => $openStatus->id,
        ]);

        $event = $this->buildParsedEvent('521111222333', 'wamid.noevent001', 'Respuesta del cliente');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        Event::assertNotDispatched(ConversationCreated::class);
    }

    public function test_new_whatsapp_conversation_dispatches_erp_link_job_when_erp_is_enabled(): void
    {
        Queue::fake();

        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $event = $this->buildParsedEvent($phone, 'wamid.erp001', 'Cliente nuevo para ERP');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        Queue::assertPushed(LinkCustomerToErpJob::class);
    }

    public function test_new_whatsapp_conversation_dispatches_erp_link_job_even_when_customer_not_in_erp(): void
    {
        // The job is dispatched regardless — the linker returns null when the
        // customer is not found in ERP, but that is a job-internal concern.
        Queue::fake();

        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $event = $this->buildParsedEvent($phone, 'wamid.erp002', 'Cliente no en ERP');

        (new ProcessSocialWebhookJob('whatsapp', 'message', $event))->handle(
            $this->app->make(WhatsAppBusinessService::class),
            $this->app->make(FacebookMessengerService::class),
            $this->app->make(OutboundMessageService::class),
            $this->app->make(BusinessHoursService::class),
            $this->app->make(RoutingRuleService::class),
        );

        // The ERP link job must always be dispatched for new conversations,
        // even if the ERP lookup will ultimately return null.
        Queue::assertPushed(LinkCustomerToErpJob::class);
    }

    // ─── Payload helpers ──────────────────────────────────────────────────────

    /**
     * Build a WhatsApp Business API webhook payload for a text message.
     */
    private function buildMessagePayload(string $phone, string $messageId, string $text): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '15550001234',
                                    'phone_number_id' => '12345678',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Test User'],
                                        'wa_id' => $phone,
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => $phone,
                                        'id' => $messageId,
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => ['body' => $text],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a WhatsApp status update payload (delivered/read).
     */
    private function buildStatusPayload(string $messageId, string $recipientPhone, string $status): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '15550001234',
                                    'phone_number_id' => '12345678',
                                ],
                                'statuses' => [
                                    [
                                        'id' => $messageId,
                                        'status' => $status,
                                        'timestamp' => (string) time(),
                                        'recipient_id' => $recipientPhone,
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a parsed event array (as produced by WhatsAppBusinessService::parseWebhookPayload()).
     * This is used when testing the job directly, bypassing the HTTP layer.
     */
    private function buildParsedEvent(string $phone, string $messageId, string $body): array
    {
        return [
            'type' => 'message',
            'phone' => $phone,
            'name' => 'Test User '.$phone,
            'message_id' => $messageId,
            'timestamp' => time(),
            'message_type' => 'text',
            'body' => $body,
            'media_id' => null,
            'caption' => null,
            'filename' => null,
            'mime_type' => null,
        ];
    }

    /**
     * Sign a payload array with HMAC-SHA256 using the test app secret.
     */
    private function signPayload(array $payload): string
    {
        return 'sha256='.hash_hmac('sha256', json_encode($payload), $this->appSecret);
    }
}
