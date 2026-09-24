<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Actions\Customers\CustomerMergeAction;
use Modules\Helpdesk\Models\Company;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;
use Modules\HelpdeskContacts\Jobs\ExportContactsJob;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskContacts\Services\ContactChatsService;
use Modules\HelpdeskContacts\Services\ContactCsvWriter;
use Modules\HelpdeskTickets\Models\Ticket;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Backend añadido para cerrar el mockup de Contactos 360: empresa en Editar,
 * importación sin actualizar existentes + filas rechazadas descargables,
 * exportación grande por email, desglose de salud y duplicados con su pareja.
 */
class ContactsMockupParityTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = [null, 'mysql', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        foreach (['contacts.view', 'contacts.update', 'helpdesk.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'email' => 'ct-'.Str::lower(Str::random(12)).'@test.invalid',
            'phone' => null,
        ], $attributes));
    }

    public function test_edit_sets_company_by_name_reusing_an_existing_one(): void
    {
        $customer = $this->customer();
        $name = 'Construcinsa '.Str::random(6);
        $existing = Company::create(['name' => $name]);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => 'Gabriel Morales', 'company' => mb_strtoupper($name)])
            ->assertOk();

        $this->assertSame($existing->id, (int) $customer->fresh()->company_id);

        $this->actingAs($this->manager)
            ->putJson(route('contacts.update', $customer), ['name' => 'Gabriel Morales', 'company' => ''])
            ->assertOk();

        $this->assertNull($customer->fresh()->company_id);
    }

    public function test_import_can_skip_existing_contacts_and_offers_rejected_rows(): void
    {
        $existing = $this->customer(['name' => 'Nombre Antiguo']);
        $csv = "nombre,correo,movil\nNombre Nuevo,{$existing->email},600111222\n,,\nMal Email,no-es-email,\n";

        $response = $this->actingAs($this->manager)->post(route('contacts.import.process'), [
            'file' => UploadedFile::fake()->createWithContent('contactos.csv', $csv),
            'update_existing' => '0',
        ]);

        $response->assertRedirect()->assertSessionHas('import_rejected_count', 3);
        $this->assertSame('Nombre Antiguo', $existing->fresh()->name);
        $rejectedUrl = session('import_rejected_url');

        $download = $this->actingAs($this->manager)->get($rejectedUrl);
        $download->assertOk();
        $body = $download->streamedContent();
        $this->assertStringContainsString('ya existe (no se actualizan existentes)', $body);
        $this->assertStringContainsString('sin nombre ni email', $body);
        $this->assertStringContainsString('email con formato inválido', $body);

        // Solo el agente que importó puede descargarlas.
        $other = User::factory()->create();
        $other->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);
        $this->actingAs($other)->get($rejectedUrl)->assertNotFound();
    }

    public function test_import_maps_the_movil_column_to_phone(): void
    {
        $email = 'movil-'.Str::lower(Str::random(8)).'@test.invalid';

        $this->actingAs($this->manager)->post(route('contacts.import.process'), [
            'file' => UploadedFile::fake()->createWithContent('contactos.csv', "nombre,correo,movil\nAna,{$email},600111222\n"),
        ])->assertRedirect();

        $this->assertSame('600111222', Customer::query()->where('email', $email)->value('phone'));
    }

    public function test_export_job_mails_a_signed_link_only_its_owner_can_download(): void
    {
        Storage::fake('local');
        Mail::fake();
        $customer = $this->customer(['name' => 'Exportado']);

        (new ExportContactsJob($this->manager->id, [$customer->id], false, false))
            ->handle(app(ContactCsvWriter::class));

        $files = Storage::disk('local')->allFiles('contacts-exports/'.$this->manager->id);
        $this->assertCount(1, $files);
        $this->assertStringContainsString('Exportado', Storage::disk('local')->get($files[0]));

        $url = URL::temporarySignedRoute('contacts.export.file', now()->addHour(), ['path' => base64_encode($files[0])]);
        $this->actingAs($this->manager)->get($url)->assertOk();

        $other = User::factory()->create();
        $other->givePermissionTo(['contacts.view', 'helpdesk.manage']);
        $this->actingAs($other)->get($url)->assertNotFound();
        $this->actingAs($this->manager)->get($url.'x')->assertForbidden();
    }

    public function test_health_factors_add_up_to_the_score(): void
    {
        $customer = $this->customer();
        $insights = app(CustomerInsightsService::class);

        $factors = $insights->healthFactors($customer);

        $this->assertCount(4, $factors);
        $this->assertSame(
            max(0, min(100, 50 + array_sum(array_column($factors, 'points')))),
            $insights->healthScore($customer)
        );
    }

    public function test_duplicate_matches_include_the_partner_id(): void
    {
        $older = $this->customer(['whatsapp_phone' => '34611'.random_int(100000, 999999)]);
        $newer = $this->customer(['whatsapp_phone' => $older->whatsapp_phone]);

        $matches = app(ContactAggregatorService::class)->duplicateMatchesFor([$newer], $this->manager);

        $this->assertSame($older->id, $matches[$newer->id]['partnerId']);
        $this->assertStringStartsWith('Mismo teléfono que', $matches[$newer->id]['reason']);
    }

    public function test_ticket_keeps_priority_and_attaches_the_360_context_as_internal_note(): void
    {
        if (! class_exists(Ticket::class)) {
            $this->markTestSkipped('HelpdeskTickets no está instalado.');
        }

        $customer = $this->customer(['name' => 'Cliente Ticket']);

        $response = $this->actingAs($this->manager)->postJson(route('contacts.tickets.store', $customer), [
            'subject' => 'Pedido detenido',
            'priority' => 'urgent',
            'attach_context' => 1,
        ]);

        $response->assertCreated();
        $ticket = Ticket::find($response->json('ticket.id'));

        $this->assertSame('urgent', $ticket->priority);
        $note = $ticket->items()->where('is_internal', true)->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('Contexto de la ficha 360 (CT-'.$customer->id.')', $note->body);
    }

    public function test_merge_keeps_the_absorbed_email_as_secondary_and_it_is_searchable(): void
    {
        $base = $this->customer(['email' => 'principal-'.Str::lower(Str::random(6)).'@test.invalid']);
        $mergee = $this->customer(['email' => 'Secundario-'.Str::lower(Str::random(6)).'@test.invalid']);

        (new CustomerMergeAction($base, $mergee))->execute();

        $secondary = $base->fresh()->secondary_emails;
        $this->assertSame([mb_strtolower($mergee->email)], $secondary);
        $this->assertTrue(Customer::query()->search(mb_strtolower($mergee->email))->whereKey($base->id)->exists());
    }

    public function test_web_visits_summary_uses_the_real_page_visit_columns(): void
    {
        $customer = $this->customer();
        $db = DB::connection('helpdesk');
        foreach ([['/a', 5], ['/b', 10], ['/c?pedido=1', 600]] as [$url, $minutes]) {
            $db->table('helpdesk_page_visits')->insert([
                'customer_id' => $customer->id,
                'page_url' => 'https://tienda.test'.$url,
                'created_at' => now()->subMinutes($minutes),
                'updated_at' => now(),
            ]);
        }

        $visits = app(ContactChatsService::class)->forCustomer($customer)['visits'];

        $this->assertSame(3, $visits['pageViews']);
        $this->assertSame(2, $visits['sessions']);
        $this->assertSame(['/a', '/b', '/c?pedido=1'], array_column($visits['recent'], 'url'));
    }

    public function test_show_mounts_the_prestashop_chat_bridge_when_the_module_is_on(): void
    {
        if (! Module::find('HelpdeskPrestashop')?->isEnabled() || ! helpdesk_integration_enabled()) {
            $this->markTestSkipped('HelpdeskPrestashop o la integración están apagados.');
        }

        $customer = $this->customer();
        // Las rutas PS del cliente autorizan con CustomerPolicy::view.
        Permission::findOrCreate('helpdeskprestashop.view', 'web');
        Permission::findOrCreate('helpdesk.customers.view', 'web');
        $this->manager->givePermissionTo(['helpdeskprestashop.view', 'helpdesk.customers.view']);

        $this->actingAs($this->manager)
            ->get(route('contacts.show', $customer))
            ->assertOk()
            ->assertSee('c360-ps-host', false)
            ->assertSee('data-bv-modal-name="ps-order-workspace"', false)
            ->assertSee('data-customer-id="'.$customer->id.'"', false);
    }

    public function test_show_hides_prestashop_actions_without_prestashop_permission(): void
    {
        $contactsOnly = User::factory()->create();
        $contactsOnly->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);

        $this->actingAs($contactsOnly)
            ->get(route('contacts.show', $this->customer()))
            ->assertOk()
            ->assertDontSee('c360-ps-host', false)
            ->assertDontSee('Cuenta en la tienda')
            ->assertDontSee('Crear vale de compensación');
    }
}
