<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Mcp\McpToolContext;
use Modules\HelpdeskAgents\Models\AiAgent;
use Modules\HelpdeskAgents\Services\AgentLlmService;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Sugerencia de respuesta, de extremo a extremo: permiso, render de la ficha y
 * endpoint.
 *
 * Lo que mas importa comprobar aqui es lo que NO pasa: el borrador se devuelve
 * como texto y no se convierte en un mensaje del ticket. Un fallo en ese punto
 * no daria error — mandaria un correo a un cliente real.
 */
class TicketAiSuggestionTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $manager;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
        $this->manager->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $customer = Customer::factory()->create(['email' => 'cliente@prueba.test', 'language' => 'es']);

        $this->ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'subject' => 'Mi pedido no ha llegado',
            'description' => 'Hice el pedido hace dos semanas y sigo sin recibirlo.',
        ]);

        $this->ticket->items()->create([
            'type' => 'message',
            'body' => 'Hice el pedido hace dos semanas y sigo sin recibirlo.',
            'is_internal' => false,
        ]);

        config([
            'helpdeskagents.ticket_ai.reply_suggestions.enabled' => true,
            'helpdeskagents.ticket_ai.reply_suggestions.cache_minutes' => 0,
            'helpdeskagents.ticket_ai.reply_suggestions.use_tools' => false,
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

    private function configureAgent(): void
    {
        $agent = new AiAgent(['name' => 'Test', 'provider' => 'anthropic', 'model' => 'test-model']);
        $agent->setRawAttributes(['api_key_encrypted' => 'sk-test'] + $agent->getAttributes(), true);

        cache()->put(AgentLlmService::DEFAULT_AGENT_CACHE_KEY, $agent, 300);
    }

    private function url(): string
    {
        return route('manager.helpdesk.tickets.ai.suggest-reply', $this->ticket);
    }

    // La ficha completa (show-full, donde vivían los botones
    // ai-suggest-reply-btn/ai-summary-btn probados aquí) se eliminó el
    // 8-sep-2026 — el listado tiene su propio botón "IA" en el composer
    // (tickets-app/core.js), sin una vista dedicada que probar en su lugar.

    public function test_it_returns_a_draft_without_touching_the_ticket(): void
    {
        $this->configureAgent();

        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => '{"template_id": null, "draft": "Lamentamos el retraso. Estamos revisando su envio.", "confidence": 0.8}']],
        ])]);

        $itemsBefore = $this->ticket->items()->count();

        $response = $this->actingAs($this->manager)->postJson($this->url());

        $response->assertOk();
        $response->assertJsonPath('suggestion.draft', 'Lamentamos el retraso. Estamos revisando su envio.');
        $response->assertJsonPath('suggestion.language', 'es');

        // La garantia central: el borrador es SOLO texto de vuelta. Ni mensaje
        // nuevo en el hilo, ni ticket modificado, ni nada enviado al cliente.
        $this->assertSame($itemsBefore, $this->ticket->items()->count());
    }

    public function test_it_reports_no_suggestion_instead_of_failing_without_an_ai_agent(): void
    {
        cache()->forget(AgentLlmService::DEFAULT_AGENT_CACHE_KEY);
        Http::fake();

        $response = $this->actingAs($this->manager)->postJson($this->url());

        // Fail-silent: la ausencia de IA no es un error del ticket.
        $response->assertOk();
        $response->assertJsonPath('suggestion', null);
        Http::assertNothingSent();
    }

    public function test_it_uses_mcp_tools_and_reports_what_it_consulted(): void
    {
        config(['helpdeskagents.ticket_ai.reply_suggestions.use_tools' => true]);
        $this->configureAgent();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                // Turno 1: el modelo pide mirar los tickets anteriores del
                // cliente. get-ticket-history solo toca la BD local, asi que el
                // test no depende del ERP ni de la tienda.
                ->push(['content' => [
                    ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get-ticket-history', 'input' => ['limit' => 3]],
                ]])
                // Turno 2: ya con el dato, redacta.
                ->push(['content' => [
                    ['type' => 'text', 'text' => '{"template_id": null, "draft": "Veo que ya nos escribio por esto.", "confidence": 0.9}'],
                ]]),
        ]);

        $response = $this->actingAs($this->manager)->postJson($this->url());

        $response->assertOk();
        $response->assertJsonPath('suggestion.draft', 'Veo que ya nos escribio por esto.');
        // Las fuentes son lo que deja al agente verificar de donde sale cada dato.
        $response->assertJsonPath('suggestion.sources', ['get-ticket-history']);

        // El ambito debe quedar liberado al terminar: en un worker el
        // contenedor se reutiliza y un ambito pegado filtraria este cliente al
        // siguiente ticket.
        $this->assertFalse(app(McpToolContext::class)->isLocked());
    }

    public function test_the_tool_scope_is_pinned_to_the_ticket_customer(): void
    {
        config(['helpdeskagents.ticket_ai.reply_suggestions.use_tools' => true]);
        $this->configureAgent();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                // Un ticket cuyo texto lo escribe el cliente puede intentar que
                // el modelo consulte a OTRO cliente. La tool debe ignorar ese
                // argumento y responder sobre el cliente del ticket.
                ->push(['content' => [
                    ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'get-ticket-history', 'input' => ['customer_email' => 'victima@otra-empresa.test']],
                ]])
                ->push(['content' => [['type' => 'text', 'text' => '{"draft": "Texto.", "confidence": 0.5}']]]),
        ]);

        $this->actingAs($this->manager)->postJson($this->url())->assertOk();

        // El resultado devuelto al modelo lleva el email del ticket, no el que
        // pidio: la tool resolvio el ambito por McpToolContext.
        $toolResult = Http::recorded()->last()[0]->data()['messages'][2]['content'][0]['content'];

        $this->assertStringContainsString('cliente@prueba.test', $toolResult);
        $this->assertStringNotContainsString('victima@otra-empresa.test', $toolResult);
    }

    public function test_it_requires_the_update_permission(): void
    {
        // helpdesk-agent, no super-settings: ese ultimo pasa por el Gate::before
        // de Auth y se salta cualquier comprobacion de permiso, asi que con el
        // este test daria verde sin ejercitar nada.
        $viewer = User::factory()->create();
        $viewer->assignRole('helpdesk-agent');
        $viewer->givePermissionTo('helpdesk.tickets.view');

        $this->actingAs($viewer)->postJson($this->url())->assertForbidden();
    }

    public function test_guests_are_rejected(): void
    {
        $this->postJson($this->url())->assertUnauthorized();
    }
}
