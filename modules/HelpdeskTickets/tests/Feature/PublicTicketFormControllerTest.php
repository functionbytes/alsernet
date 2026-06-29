<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketCategoryField;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

class PublicTicketFormControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=0');
        DB::connection('helpdesk')->table('helpdesk_ticket_attachments')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_messages')->truncate();
        DB::connection('helpdesk')->table('helpdesk_tickets')->truncate();
        DB::connection('helpdesk')->table('helpdesk_customers')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_category_fields')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_categories')->truncate();
        DB::connection('helpdesk')->table('helpdesk_ticket_statuses')->truncate();
        DB::connection('helpdesk')->table('helpdesk_channel_webs')->truncate();
        DB::connection('helpdesk')->statement('SET FOREIGN_KEY_CHECKS=1');

        // Ensure a default TicketStatus exists so TicketService can assign it
        TicketStatus::firstOrCreate(
            ['slug' => 'new'],
            [
                'name' => 'Nuevo',
                'color' => '#22c55e',
                'is_open' => true,
                'is_default' => true,
                'is_system' => true,
                'stops_sla_timer' => false,
                'active' => true,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // GET /hd/api/ticket-forms/{slug}
    // -------------------------------------------------------------------------

    public function test_show_returns_category_with_fields(): void
    {
        $category = TicketCategory::factory()->create(['active' => true]);

        TicketCategoryField::create([
            'ticket_category_id' => $category->id,
            'type' => 'text',
            'key' => 'campo_uno',
            'label' => 'Campo Uno',
            'is_required' => true,
            'is_visible' => true,
            'width' => 'full',
            'sort_order' => 1,
        ]);

        TicketCategoryField::create([
            'ticket_category_id' => $category->id,
            'type' => 'number',
            'key' => 'campo_dos',
            'label' => 'Campo Dos',
            'is_required' => false,
            'is_visible' => true,
            'width' => 'half',
            'sort_order' => 2,
        ]);

        $response = $this->getJson("/hd/api/ticket-forms/{$category->slug}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.slug', $category->slug)
            ->assertJsonCount(2, 'data.fields');
    }

    public function test_show_returns_404_for_inactive_category(): void
    {
        $category = TicketCategory::factory()->inactive()->create();

        $this->getJson("/hd/api/ticket-forms/{$category->slug}")
            ->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_slug(): void
    {
        $this->getJson('/hd/api/ticket-forms/slug-que-no-existe')
            ->assertNotFound();
    }

    public function test_show_returns_empty_fields_when_none_defined(): void
    {
        $category = TicketCategory::factory()->create(['active' => true]);

        $response = $this->getJson("/hd/api/ticket-forms/{$category->slug}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.fields');
    }

    // -------------------------------------------------------------------------
    // POST /hd/api/ticket-forms/{slug}/submit
    // -------------------------------------------------------------------------

    public function test_submit_creates_ticket_with_valid_data(): void
    {
        Event::fake([TicketCreated::class]);

        $category = TicketCategory::factory()->create(['active' => true]);

        $response = $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Necesito soporte',
            'description' => 'Tengo un problema con mi cuenta.',
            'customer_email' => 'cliente@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'ticket_number']]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'subject' => 'Necesito soporte',
            'source' => 'web_form',
        ], 'helpdesk');
    }

    public function test_submit_creates_customer_if_not_exists(): void
    {
        Event::fake([TicketCreated::class]);

        $category = TicketCategory::factory()->create(['active' => true]);
        $newEmail = 'nuevo@example.com';

        $this->assertDatabaseMissing('helpdesk_customers', ['email' => $newEmail], 'helpdesk');

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Primera solicitud',
            'description' => 'Descripcion de la solicitud.',
            'customer_email' => $newEmail,
            'customer_name' => 'Cliente Nuevo',
        ])->assertCreated();

        $this->assertDatabaseHas('helpdesk_customers', [
            'email' => $newEmail,
            'name' => 'Cliente Nuevo',
        ], 'helpdesk');
    }

    public function test_submit_reuses_existing_customer(): void
    {
        Event::fake([TicketCreated::class]);

        $category = TicketCategory::factory()->create(['active' => true]);
        $customer = Customer::factory()->create(['email' => 'existente@example.com']);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Segundo ticket',
            'description' => 'Del mismo cliente.',
            'customer_email' => 'existente@example.com',
        ])->assertCreated();

        $this->assertSame(1, Customer::where('email', 'existente@example.com')->count());

        $this->assertDatabaseHas('helpdesk_tickets', [
            'customer_id' => $customer->id,
        ], 'helpdesk');
    }

    public function test_submit_validates_required_fields(): void
    {
        $category = TicketCategory::factory()->create(['active' => true]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'description', 'customer_email']);
    }

    public function test_submit_returns_404_for_inactive_category(): void
    {
        $category = TicketCategory::factory()->inactive()->create();

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Test',
            'description' => 'Test description.',
            'customer_email' => 'test@example.com',
        ])->assertNotFound();
    }

    public function test_submit_validates_custom_required_field(): void
    {
        $category = TicketCategory::factory()->create(['active' => true]);

        TicketCategoryField::create([
            'ticket_category_id' => $category->id,
            'type' => 'text',
            'key' => 'numero_pedido',
            'label' => 'Número de pedido',
            'is_required' => true,
            'is_visible' => true,
            'width' => 'full',
            'sort_order' => 1,
        ]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Soporte',
            'description' => 'Necesito ayuda.',
            'customer_email' => 'cliente@example.com',
            // numero_pedido ausente
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['numero_pedido']);
    }

    public function test_submit_dispatches_ticket_created_event(): void
    {
        Event::fake([TicketCreated::class]);

        $category = TicketCategory::factory()->create(['active' => true]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Ticket de prueba',
            'description' => 'Verificando despacho de evento.',
            'customer_email' => 'eventos@example.com',
        ])->assertCreated();

        Event::assertDispatched(TicketCreated::class);
    }

    public function test_submit_stores_custom_fields_in_ticket(): void
    {
        Event::fake([TicketCreated::class]);

        $category = TicketCategory::factory()->create(['active' => true]);

        TicketCategoryField::create([
            'ticket_category_id' => $category->id,
            'type' => 'number',
            'key' => 'numero_orden',
            'label' => 'Número de orden',
            'is_required' => false,
            'is_visible' => true,
            'width' => 'half',
            'sort_order' => 1,
        ]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Consulta de orden',
            'description' => 'Consulta sobre mi orden.',
            'customer_email' => 'orden@example.com',
            'numero_orden' => 12345,
        ])->assertCreated();

        $ticket = Ticket::first();

        $this->assertNotNull($ticket);
        $this->assertArrayHasKey('numero_orden', $ticket->custom_fields ?? []);
        $this->assertEquals(12345, $ticket->custom_fields['numero_orden']);
    }

    // -------------------------------------------------------------------------
    // website_token validation (Gap 1)
    // -------------------------------------------------------------------------

    public function test_submit_accepts_valid_website_token(): void
    {
        Event::fake([TicketCreated::class]);

        $web = Web::factory()->create([
            'website_token' => 'valid-token-123',
        ]);

        $category = TicketCategory::factory()->create(['active' => true]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Ticket con token válido',
            'description' => 'Descripcion del ticket.',
            'customer_email' => 'token@example.com',
            'website_token' => 'valid-token-123',
        ])->assertCreated();
    }

    public function test_submit_rejects_invalid_website_token(): void
    {
        $category = TicketCategory::factory()->create(['active' => true]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Ticket con token inválido',
            'description' => 'Descripcion del ticket.',
            'customer_email' => 'badtoken@example.com',
            'website_token' => 'invalid-token-xyz',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Token de widget no válido.']);
    }

    public function test_submit_without_website_token_succeeds(): void
    {
        Event::fake([TicketCreated::class]);

        $category = TicketCategory::factory()->create(['active' => true]);

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Ticket sin token',
            'description' => 'Descripcion del ticket.',
            'customer_email' => 'notoken@example.com',
        ])->assertCreated();
    }

    // -------------------------------------------------------------------------
    // File upload (Gap 2)
    // -------------------------------------------------------------------------

    public function test_submit_stores_file_attachment_when_field_type_is_file(): void
    {
        Event::fake([TicketCreated::class]);
        Storage::fake('local');

        $category = TicketCategory::factory()->create(['active' => true]);

        TicketCategoryField::create([
            'ticket_category_id' => $category->id,
            'type' => 'file',
            'key' => 'adjunto',
            'label' => 'Adjunto',
            'is_required' => false,
            'is_visible' => true,
            'width' => 'full',
            'sort_order' => 1,
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Ticket con archivo',
            'description' => 'Descripcion con adjunto.',
            'customer_email' => 'archivo@example.com',
            'adjunto' => $file,
        ])->assertCreated();

        $this->assertDatabaseHas('helpdesk_ticket_attachments', [
            'filename' => 'test.pdf',
        ], 'helpdesk');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function test_submit_does_not_include_file_in_custom_fields(): void
    {
        Event::fake([TicketCreated::class]);
        Storage::fake('local');

        $category = TicketCategory::factory()->create(['active' => true]);

        TicketCategoryField::create([
            'ticket_category_id' => $category->id,
            'type' => 'file',
            'key' => 'documento',
            'label' => 'Documento',
            'is_required' => false,
            'is_visible' => true,
            'width' => 'full',
            'sort_order' => 1,
        ]);

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $this->postJson("/hd/api/ticket-forms/{$category->slug}/submit", [
            'subject' => 'Ticket con documento',
            'description' => 'Descripcion.',
            'customer_email' => 'doc@example.com',
            'documento' => $file,
        ])->assertCreated();

        $ticket = Ticket::first();

        $this->assertNotNull($ticket);
        $this->assertArrayNotHasKey('documento', $ticket->custom_fields ?? []);
    }
}
