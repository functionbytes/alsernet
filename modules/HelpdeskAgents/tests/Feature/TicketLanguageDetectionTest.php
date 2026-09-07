<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\HelpdeskAgents\Jobs\ClassifyTicketJob;
use Modules\HelpdeskAgents\Jobs\DetectTicketLanguageJob;
use Modules\HelpdeskAgents\Listeners\QueueTicketAiOnTicketCreated;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Tests\TestCase;

class TicketLanguageDetectionTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Sustituye el traductor del helpdesk por un doble.
     *
     * El job ya no habla con LibreTranslate directamente: pasa por
     * CachedTranslator, que es donde viven el proveedor configurado (DeepL por
     * defecto), el fallback entre proveedores, el circuit breaker, el cupo y la
     * caché. Antes este test fijaba el endpoint de LibreTranslate por config y
     * simulaba su HTTP; eso dejó de reflejar el camino real.
     *
     * De paso desaparece la dependencia de que la BD de test no tenga una fila
     * de ajustes de LibreTranslate, que es lo que rompía el test en un entorno
     * compartido donde alguien la había creado.
     *
     * @param  string|null  $detected  lo que devuelve el traductor, o null si ningún proveedor responde
     */
    private function fakeTranslator(?string $detected): void
    {
        $this->mock(CachedTranslator::class)
            ->shouldReceive('detectLanguage')
            ->andReturn($detected);
    }

    private function createTicket(string $firstMessage = 'Bonjour, je voudrais un remboursement s\'il vous plaît.'): Ticket
    {
        $ticket = Ticket::factory()->create(['priority' => 'normal']);

        TicketItem::query()->create([
            'ticket_id' => $ticket->id,
            'type' => 'message',
            'body' => $firstMessage,
            'is_internal' => false,
        ]);

        return $ticket;
    }

    public function test_ticket_created_listener_queues_language_detection(): void
    {
        Queue::fake();

        $ticket = $this->createTicket();

        (new QueueTicketAiOnTicketCreated)->handle(new TicketCreated($ticket));

        Queue::assertPushed(
            DetectTicketLanguageJob::class,
            fn (DetectTicketLanguageJob $job) => $job->ticketId === $ticket->id
        );

        // Auto-classification is off by default, so its job must not be queued.
        Queue::assertNotPushed(ClassifyTicketJob::class);
    }

    public function test_ticket_created_listener_queues_classification_when_enabled(): void
    {
        config()->set('helpdeskagents.ticket_ai.auto_classification', true);

        Queue::fake();

        $ticket = $this->createTicket();

        (new QueueTicketAiOnTicketCreated)->handle(new TicketCreated($ticket));

        Queue::assertPushed(ClassifyTicketJob::class);
    }

    public function test_job_stamps_detected_language_on_ticket(): void
    {
        $this->fakeTranslator('fr');

        $ticket = $this->createTicket();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        $this->assertSame('fr', $ticket->fresh()->detected_language);
    }

    public function test_existing_detected_language_is_not_overwritten(): void
    {
        Http::fake();

        // Sin doble del traductor a propósito: si el job llegara a consultarlo,
        // el mock no existiría y el fallo sería evidente. El ticket ya tiene
        // idioma, así que debe salir antes de preguntar nada.
        $ticket = $this->createTicket();
        $ticket->forceFill(['detected_language' => 'es'])->saveQuietly();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        Http::assertNothingSent();
        $this->assertSame('es', $ticket->fresh()->detected_language);
    }

    public function test_detection_failure_leaves_ticket_untouched(): void
    {
        // Ningún proveedor de traducción responde. El LLM es el último recurso
        // y aquí tampoco hay agente configurado, así que no hay idioma.
        $this->fakeTranslator(null);
        Http::fake();

        $ticket = $this->createTicket();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        $this->assertNull($ticket->fresh()->detected_language);
    }

    public function test_a_message_too_short_is_not_detected_at_all(): void
    {
        Http::fake();

        // Por debajo de 12 caracteres no se detecta: un "Ok" se identifica como
        // cualquier idioma, y un ticket mal etiquetado se enruta a quien no toca.
        // Mismo suelo que aplica CachedTranslator.
        $ticket = $this->createTicket('Ok');

        (new DetectTicketLanguageJob($ticket->id))->handle();

        Http::assertNothingSent();
        $this->assertNull($ticket->fresh()->detected_language);
    }

    public function test_feature_flag_disables_detection(): void
    {
        config()->set('helpdeskagents.ticket_ai.language_detection', false);

        Http::fake();

        $ticket = $this->createTicket();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        Http::assertNothingSent();
        $this->assertNull($ticket->fresh()->detected_language);
    }
}
