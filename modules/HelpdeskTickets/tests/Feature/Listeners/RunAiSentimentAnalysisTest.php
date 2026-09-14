<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Listeners;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Listeners\RunAiSentimentAnalysis;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketSentimentService;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Fix de lógica de negocio del 14-sep-2026 (auditoría): el listener volvía a
 * consultar "el último mensaje público del ticket" en vez de usar
 * $event->message directamente. Con dos mensajes seguidos del cliente (A,
 * luego B) encolados antes de que un worker procesara el primero, AMBOS jobs
 * resolvían el MISMO "último" (B) — A se quedaba sin sentiment para siempre
 * y B se etiquetaba dos veces.
 *
 * Este test fija el comportamiento correcto: el evento de A debe etiquetar A,
 * no B, aunque B ya exista en la base de datos como mensaje más reciente.
 */
class RunAiSentimentAnalysisTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Sin agente de IA configurado: TicketSentimentService cae al
        // heurístico de palabras, determinista y sin red — no hace falta
        // fakear ninguna llamada HTTP para este test.
        config([
            'helpdeskagents.ai_usage.enabled' => false,
            'helpdeskagents.ai_usage.daily_max_calls' => 0,
            'helpdeskagents.ai_usage.daily_max_tokens' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);

        parent::tearDown();
    }

    public function test_tags_the_message_from_the_event_not_the_latest_one(): void
    {
        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'sentiment-race-test@example.com'],
            ['name' => 'Sentiment Race Test']
        );

        $ticket = Ticket::create([
            'subject' => 'Test ticket',
            'description' => 'x',
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
            'customer_id' => $customer->id,
        ]);

        // A: mensaje del cliente, más antiguo. author_id set / user_id null
        // => isFromCustomer(). Cuerpo con tono negativo para que el
        // heurístico de palabras produzca un sentiment distinto de "neutral"
        // y así demostrar que SÍ se etiquetó (no solo que no lanzó excepción).
        $messageA = TicketItem::create([
            'ticket_id' => $ticket->id,
            'author_id' => $customer->id,
            'type' => 'message',
            'body' => 'Esto es pésimo, llevo días esperando y nadie responde.',
            'is_internal' => false,
        ]);

        // B: mensaje del cliente, más reciente — el que devolvería
        // "->latest()->first()" si el listener siguiera consultando en vez
        // de usar $event->message.
        $messageB = TicketItem::create([
            'ticket_id' => $ticket->id,
            'author_id' => $customer->id,
            'type' => 'message',
            'body' => 'Gracias, todo perfecto ahora.',
            'is_internal' => false,
        ]);

        // El evento apunta a A (el primero en encolarse), aunque B ya exista
        // en BD como fila más reciente en el momento en que el job corre.
        (new RunAiSentimentAnalysis(app(TicketSentimentService::class)))
            ->handle(new MessageAdded($messageA));

        $messageA->refresh();
        $messageB->refresh();

        $this->assertNotNull($messageA->sentiment, 'El mensaje del EVENTO debe quedar etiquetado.');
        $this->assertNull($messageB->sentiment, 'El mensaje más reciente NO debe tocarse: el evento era de A, no de B.');
    }
}
