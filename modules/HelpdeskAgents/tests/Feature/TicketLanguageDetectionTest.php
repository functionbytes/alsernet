<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskAgents\Jobs\ClassifyTicketJob;
use Modules\HelpdeskAgents\Jobs\DetectTicketLanguageJob;
use Modules\HelpdeskAgents\Listeners\QueueTicketAiOnTicketCreated;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Tests\TestCase;

class TicketLanguageDetectionTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private const ENDPOINT = 'https://libretranslate.test/translate';

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
     * TranslationService reads the endpoint from the admin Setting first and
     * only then from config. The pristine test DB has no libretranslate
     * setting rows (asserted below), so pinning the config is deterministic.
     * We deliberately do NOT insert into helpdesk_settings here: concurrent
     * suites on the shared test DB hold gap locks on that table and the
     * insert deadlocks.
     */
    private function pinEndpoint(): void
    {
        $this->assertNull(
            Setting::get('helpdesktranslate.libretranslate.endpoint'),
            'Unexpected libretranslate endpoint setting in the test DB — this test relies on the config fallback.'
        );

        config()->set('helpdesktranslate.libretranslate.endpoint', self::ENDPOINT);
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
        $this->pinEndpoint();

        Http::fake([
            'libretranslate.test/detect' => Http::response([
                ['language' => 'fr', 'confidence' => 93.0],
            ]),
        ]);

        $ticket = $this->createTicket();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        $this->assertSame('fr', $ticket->fresh()->detected_language);
    }

    public function test_existing_detected_language_is_not_overwritten(): void
    {
        $this->pinEndpoint();

        Http::fake();

        $ticket = $this->createTicket();
        $ticket->forceFill(['detected_language' => 'es'])->saveQuietly();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        Http::assertNothingSent();
        $this->assertSame('es', $ticket->fresh()->detected_language);
    }

    public function test_detection_failure_leaves_ticket_untouched(): void
    {
        $this->pinEndpoint();

        Http::fake(['libretranslate.test/*' => Http::response([], 500)]);

        $ticket = $this->createTicket();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        $this->assertNull($ticket->fresh()->detected_language);
    }

    public function test_feature_flag_disables_detection(): void
    {
        config()->set('helpdeskagents.ticket_ai.language_detection', false);
        $this->pinEndpoint();

        Http::fake();

        $ticket = $this->createTicket();

        (new DetectTicketLanguageJob($ticket->id))->handle();

        Http::assertNothingSent();
        $this->assertNull($ticket->fresh()->detected_language);
    }
}
