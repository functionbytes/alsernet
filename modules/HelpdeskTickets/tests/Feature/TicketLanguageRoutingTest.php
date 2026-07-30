<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Listeners\AutoAssignNewTicket;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Ruteo por idioma en la auto-asignación de tickets: con el toggle
 * auto_assign.language_routing activo se prefieren agentes que hablan el
 * detected_language del ticket (AgentSettings.languages); sin hablantes se
 * cae al pool completo (nunca se deja un ticket sin asignar por idioma) y
 * con el toggle off (default) el comportamiento actual queda intacto.
 */
class TicketLanguageRoutingTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $openStatus;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('helpdesk-agent', 'web');

        Setting::query()->where('key', 'like', 'auto_assign.%')->delete();

        Mail::fake();

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->customer = Customer::create([
            'name' => 'Language Routing Customer',
            'email' => 'language-routing@example.com',
        ]);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function enable(bool $languageRouting): void
    {
        config()->set('helpdesk.auto_assignment.enabled', true);
        config()->set('helpdesk.auto_assignment.strategy', 'round_robin');
        config()->set('helpdesk.auto_assignment.language_routing', $languageRouting);
    }

    /**
     * @param  list<string>|null  $languages
     */
    private function createAgent(string $firstname, string $email, ?array $languages = null): User
    {
        $user = User::factory()->create([
            'firstname' => $firstname,
            'lastname' => 'Agent',
            'email' => $email,
        ]);

        $user->assignRole('helpdesk-agent');

        AgentSettings::create([
            'user_id' => $user->id,
            'is_available' => true,
            'accepts_conversations' => 'yes',
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
            'languages' => $languages,
        ]);

        return $user;
    }

    private function makeTicket(?string $detectedLanguage = null): Ticket
    {
        $ticket = Ticket::create([
            'subject' => 'Language routing ticket',
            'description' => 'Ticket for language routing tests.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
        ]);

        if ($detectedLanguage !== null) {
            // Igual que DetectTicketLanguageJob: la columna no es fillable.
            $ticket->forceFill(['detected_language' => $detectedLanguage])->saveQuietly();
        }

        return $ticket->fresh();
    }

    private function handle(Ticket $ticket): void
    {
        (new AutoAssignNewTicket(app(AssignmentService::class)))
            ->handle(new TicketCreated($ticket));
    }

    // ─── routing on ──────────────────────────────────────────────────────────

    public function test_prefers_agent_that_speaks_detected_language(): void
    {
        $this->enable(languageRouting: true);

        $this->createAgent('English', 'en-only@example.com', ['en']);
        $spanish = $this->createAgent('Spanish', 'es-speaker@example.com', ['en', 'es']);

        $ticket = $this->makeTicket('es');
        $this->handle($ticket);

        $this->assertSame($spanish->id, $ticket->fresh()->assignee_id);
    }

    public function test_language_match_uses_primary_subtag(): void
    {
        $this->enable(languageRouting: true);

        $this->createAgent('English', 'en-gb@example.com', ['en-GB']);
        $french = $this->createAgent('French', 'fr-ca@example.com', ['fr-CA']);

        // detected_language regional ("fr-FR") matchea al agente "fr-CA".
        $ticket = $this->makeTicket('fr-FR');
        $this->handle($ticket);

        $this->assertSame($french->id, $ticket->fresh()->assignee_id);
    }

    public function test_falls_back_to_full_pool_when_no_agent_speaks_language(): void
    {
        $this->enable(languageRouting: true);

        $agent = $this->createAgent('OnlyOne', 'only-english@example.com', ['en']);

        $ticket = $this->makeTicket('de');
        $this->handle($ticket);

        // Nadie habla alemán: el ticket NO se queda sin asignar.
        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
    }

    public function test_ticket_without_detected_language_ignores_language_routing(): void
    {
        $this->enable(languageRouting: true);

        $agent = $this->createAgent('NoLang', 'no-language@example.com', ['en']);

        $ticket = $this->makeTicket(null);
        $this->handle($ticket);

        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
    }

    public function test_runtime_setting_enables_language_routing_over_config(): void
    {
        // Config off pero setting runtime on.
        $this->enable(languageRouting: false);
        Setting::set('auto_assign.language_routing', '1', 'auto_assign');

        $this->createAgent('English', 'runtime-en@example.com', ['en']);
        $spanish = $this->createAgent('Spanish', 'runtime-es@example.com', ['es']);

        $ticket = $this->makeTicket('es');
        $this->handle($ticket);

        $this->assertSame($spanish->id, $ticket->fresh()->assignee_id);
    }

    // ─── toggle off (default) ────────────────────────────────────────────────

    public function test_toggle_off_keeps_current_behaviour_and_ignores_language(): void
    {
        $this->enable(languageRouting: false);

        // Solo existe un agente que NO habla el idioma del ticket: con el
        // toggle off el idioma no influye y el agente lo recibe igualmente.
        $agent = $this->createAgent('Regular', 'regular-agent@example.com', ['en']);

        $ticket = $this->makeTicket('es');
        $this->handle($ticket);

        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
    }

    public function test_toggle_off_round_robin_does_not_filter_by_language(): void
    {
        $this->enable(languageRouting: false);

        // Dos agentes, uno hablante y otro no: con el toggle off ambos son
        // igualmente elegibles (round-robin puro, sin preferencia de idioma).
        $first = $this->createAgent('First', 'off-first@example.com', null);
        $second = $this->createAgent('Second', 'off-second@example.com', ['es']);

        $ticket = $this->makeTicket('es');
        $this->handle($ticket);

        $assigneeId = $ticket->fresh()->assignee_id;
        $this->assertNotNull($assigneeId);
        $this->assertContains($assigneeId, [$first->id, $second->id]);
    }

    // ─── infra ───────────────────────────────────────────────────────────────

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
