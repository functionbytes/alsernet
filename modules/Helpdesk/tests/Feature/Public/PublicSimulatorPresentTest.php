<?php

namespace Modules\Helpdesk\Tests\Feature\Public;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Public\PublicSimulatorService;
use Modules\Helpdesk\Tests\HelpdeskTestCase;

/**
 * SendGreetingOnConversationCreated / RespondOffHoursOnConversationCreated /
 * SendFarewellOnConversationClosed crean su ConversationItem con user_id null (no hay
 * agente humano al que atribuirlo) y metadata.auto_reply. Antes de este fix,
 * present() solo miraba user_id/injected_as_agent, asi que esas respuestas
 * automaticas se presentaban 'from' => 'customer' y el simulador las pintaba
 * como si el cliente se las hubiera escrito a si mismo.
 */
class PublicSimulatorPresentTest extends HelpdeskTestCase
{
    private PublicSimulatorService $service;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PublicSimulatorService::class);

        $status = ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->conversation = Conversation::create([
            'customer_id' => Customer::factory()->create()->id,
            'channel' => 'web',
            'subject' => 'Prueba',
            'status_id' => $status->id,
        ]);
    }

    private function makeItem(array $overrides = []): ConversationItem
    {
        return ConversationItem::create(array_merge([
            'conversation_id' => $this->conversation->id,
            'user_id' => null,
            'type' => 'message',
            'body' => 'Hola',
            'is_internal' => false,
        ], $overrides));
    }

    public function test_auto_reply_metadata_presents_as_agent(): void
    {
        foreach (['greeting', 'off_hours', 'farewell'] as $type) {
            $item = $this->makeItem(['body' => 'Auto '.$type, 'metadata' => ['auto_reply' => $type]]);

            $presented = $this->service->present($item);

            $this->assertSame('agent', $presented['from'], "auto_reply={$type} debe presentarse como agente");
            $this->assertSame('Agente', $presented['sender_name']);
        }
    }

    public function test_customer_message_without_user_id_still_presents_as_customer(): void
    {
        $item = $this->makeItem(['body' => 'Quiero comprar un producto']);

        $presented = $this->service->present($item);

        $this->assertSame('customer', $presented['from']);
        $this->assertSame('Tú', $presented['sender_name']);
    }

    public function test_message_with_real_user_id_presents_as_agent_with_their_name(): void
    {
        $agent = User::factory()->create(['firstname' => 'Ana', 'lastname' => 'Agente']);
        $item = $this->makeItem(['user_id' => $agent->id, 'body' => 'Como puedo ayudarte']);

        $presented = $this->service->present($item);

        $this->assertSame('agent', $presented['from']);
        $this->assertSame('Ana Agente', $presented['sender_name']);
    }
}
