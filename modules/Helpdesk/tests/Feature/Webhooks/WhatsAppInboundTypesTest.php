<?php

namespace Modules\Helpdesk\Tests\Feature\Webhooks;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\WhatsAppBusinessService;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class WhatsAppInboundTypesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // Los listeners de inbound consultan User::role('helpdesk-agent').
        $this->seedHelpdeskRoles();
    }

    private function payloadWithMessage(array $message): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [$message],
                    ],
                ]],
            ]],
        ];
    }

    public function test_parser_emite_evento_reaction(): void
    {
        $events = (new WhatsAppBusinessService)->parseWebhookPayload($this->payloadWithMessage([
            'from' => '5211234567890',
            'id' => 'wamid.x',
            'timestamp' => (string) time(),
            'type' => 'reaction',
            'reaction' => ['message_id' => 'wamid.agent', 'emoji' => '👍'],
        ]));

        $this->assertSame('reaction', $events[0]['type']);
        $this->assertSame('wamid.agent', $events[0]['message_id']);
        $this->assertSame('👍', $events[0]['emoji']);
        $this->assertSame('react', $events[0]['action']);
        $this->assertSame('5211234567890', $events[0]['recipient_id']);
    }

    public function test_parser_trata_emoji_vacio_como_unreact(): void
    {
        $events = (new WhatsAppBusinessService)->parseWebhookPayload($this->payloadWithMessage([
            'from' => '5211234567890',
            'id' => 'wamid.x',
            'timestamp' => (string) time(),
            'type' => 'reaction',
            'reaction' => ['message_id' => 'wamid.agent', 'emoji' => ''],
        ]));

        $this->assertSame('unreact', $events[0]['action']);
    }

    public function test_parser_captura_referral_de_anuncio_click_to_whatsapp(): void
    {
        $events = (new WhatsAppBusinessService)->parseWebhookPayload($this->payloadWithMessage([
            'from' => '5211234567890',
            'id' => 'wamid.x',
            'timestamp' => (string) time(),
            'type' => 'text',
            'text' => ['body' => 'Hola, vi vuestro anuncio'],
            'referral' => ['source_type' => 'ad', 'source_id' => 'ad_123', 'headline' => 'Oferta'],
        ]));

        $this->assertSame('message', $events[0]['type']);
        $this->assertSame('ad_123', $events[0]['referral']['source_id'] ?? null);
    }

    public function test_reaction_se_adjunta_al_mensaje_saliente_del_agente(): void
    {
        $phone = '521'.fake()->numberBetween(1000000000, 9999999999);
        $messageId = 'wamid.'.Str::random(20);

        $customer = Customer::factory()->create(['whatsapp_phone' => $phone]);
        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1],
        );
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'external_sender_id' => $phone,
            'status_id' => $status->id,
        ]);
        $agent = User::factory()->create();
        $item = ConversationItem::factory()->fromAgent($agent->id)->create([
            'conversation_id' => $conversation->id,
            'external_id' => $messageId,
            'body' => 'Respuesta del agente',
        ]);

        $event = [
            'type' => 'reaction',
            'recipient_id' => $phone,
            'message_id' => $messageId,
            'emoji' => '❤️',
            'action' => 'react',
            'timestamp' => time(),
        ];

        (new ProcessSocialWebhookJob('whatsapp', 'reaction', $event))->handle(
            $this->app->make(FacebookMessengerService::class),
        );

        $reactions = $item->fresh()->metadata['customer_reactions'] ?? [];
        $this->assertNotEmpty($reactions);
        $this->assertSame('❤️', $reactions[0]['emoji'] ?? null);
    }
}
