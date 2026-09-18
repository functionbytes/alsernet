<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Services\EmailInboundService;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Tests\TestCase;

/**
 * El correo que entra al inbox tiene que buscar al cliente en el ERP.
 *
 * EmailInboundService creaba la conversación con Conversation::create() a secas
 * y sin emitir ConversationCreated, así que el canal de correo era el único que
 * no vinculaba nada: chat web y los canales de Meta sí lo hacían, porque ellos
 * sí emiten el evento.
 */
class EmailInboundErpLinkTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_incoming_email_asks_the_erp_for_the_customer(): void
    {
        Queue::fake();

        $conversation = app(EmailInboundService::class)->process($this->payload());

        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $job) use ($conversation) {
            return $this->jobProperty($job, 'sourceType') === 'conversation'
                && $this->jobProperty($job, 'sourceId') === $conversation->id;
        });
    }

    /**
     * El caso que no cubría nadie: si el primer intento falló, o el cliente aún
     * no estaba de alta en gestión, una respuesta al mismo hilo tiene que
     * volver a preguntar.
     */
    public function test_reply_to_an_existing_thread_asks_again(): void
    {
        $first = app(EmailInboundService::class)->process($this->payload());

        Queue::fake();

        $second = app(EmailInboundService::class)->process($this->payload([
            'subject' => "Re: [CONV-{$first->id}] Consulta",
            'message_id' => '<'.Str::uuid().'@test.example>',
        ]));

        // Sin SPF/DKIM alineado no se permite continuar el hilo ajeno, así que
        // aquí puede nacer otra conversación; lo que importa es que en ambos
        // casos se vuelva a preguntar al ERP, que es lo que antes no pasaba.
        $this->assertInstanceOf(Conversation::class, $second);

        Queue::assertPushed(LinkCustomerToErpJob::class);
    }

    /* ── El interruptor del evento ────────────────────────────────────────── */

    public function test_conversation_created_stays_off_by_default(): void
    {
        Queue::fake();
        Event::fake([ConversationCreated::class]);

        config(['helpdesk.email_inbound.dispatch_conversation_created' => false]);

        app(EmailInboundService::class)->process($this->payload());

        // Apagado a propósito: encenderlo hace que cada correo entrante pueda
        // recibir saludo automático y respuesta fuera de horario.
        Event::assertNotDispatched(ConversationCreated::class);
    }

    public function test_conversation_created_is_emitted_when_the_switch_is_on(): void
    {
        Queue::fake();
        Event::fake([ConversationCreated::class]);

        config(['helpdesk.email_inbound.dispatch_conversation_created' => true]);

        app(EmailInboundService::class)->process($this->payload());

        Event::assertDispatched(ConversationCreated::class);
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'from' => Str::lower(Str::replace('-', '', Str::uuid())).'@test.example',
            'from_name' => 'Cliente de prueba',
            'subject' => 'Consulta',
            'body' => 'Quiero saber el estado de mi pedido.',
            'message_id' => '<'.Str::uuid().'@test.example>',
        ], $overrides);
    }

    private function jobProperty(LinkCustomerToErpJob $job, string $name): mixed
    {
        return (new \ReflectionProperty($job, $name))->getValue($job);
    }
}
