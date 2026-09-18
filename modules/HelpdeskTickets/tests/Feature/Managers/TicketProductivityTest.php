<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Endpoints de productividad del agente: traducción del borrador y aplicación
 * de sugerencias de IA (categoría / prioridad).
 */
class TicketProductivityTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $agent;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-settings');
        $this->agent->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->customer = Customer::firstOrCreate(
            ['email' => 'productivity@example.com'],
            ['name' => 'Productivity Customer']
        );
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_translate_degrades_to_original_text_when_translate_disabled(): void
    {
        Setting::set('translate.integration_enabled', '0');

        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.translate', $ticket), [
                'text' => 'Hola mundo',
                'target_lang' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('translated', false)
            ->assertJsonPath('text', 'Hola mundo');
    }

    public function test_translate_validates_required_fields(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.translate', $ticket), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'target_lang']);
    }

    public function test_apply_ai_suggestion_sets_priority_and_clears_the_suggestion(): void
    {
        $ticket = $this->ticket(['priority' => 'normal', 'ai_suggested_priority' => 'urgent']);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.apply-ai-suggestion', $ticket), ['field' => 'priority'])
            ->assertOk()
            ->assertJsonPath('data.priority', 'urgent');

        $fresh = $ticket->fresh();
        $this->assertSame('urgent', $fresh->priority);
        $this->assertNull($fresh->ai_suggested_priority);
    }

    public function test_apply_ai_suggestion_sets_category_and_clears_the_suggestion(): void
    {
        $category = TicketCategory::factory()->create(['active' => true]);
        $ticket = $this->ticket(['category_id' => null, 'ai_suggested_category_id' => $category->id]);

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.apply-ai-suggestion', $ticket), ['field' => 'category'])
            ->assertOk();

        $fresh = $ticket->fresh();
        $this->assertSame($category->id, $fresh->category_id);
        $this->assertNull($fresh->ai_suggested_category_id);
    }

    public function test_apply_ai_suggestion_rejects_invalid_field(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.apply-ai-suggestion', $ticket), ['field' => 'status'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['field']);
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Productivity ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
