<?php

namespace Modules\HelpdeskContacts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Laravel\Pulse\Pulse;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskContacts\Database\Seeders\HelpdeskContactsPermissionsSeeder;
use Tests\Concerns\SeedsCorePermissions;
use Tests\TestCase;

/**
 * Hallazgos de auditoría en ContactsController::importProcess:
 *  - el match por email ignoraba forAgent() → un agente podía modificar
 *    contactos de otras bandejas (IDOR de escritura);
 *  - los contactos creados no se asociaban a ninguna bandeja (invisibles para
 *    el agente restringido que los importó);
 *  - faltaba el gate helpdesk_contacts_enabled();
 *  - los emails no se validaban por fila.
 */
class ContactsImportTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsCorePermissions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // Pulse se usa en CustomerInsightsService; stub para no tocar su storage.
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

        $this->seedCorePermissions();
        $this->seed(HelpdeskContactsPermissionsSeeder::class);
    }

    private function makeRestrictedAgent(Inbox $inbox): User
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['contacts.view', 'contacts.update']);

        AgentInboxCapacity::create([
            'user_id' => $agent->id,
            'inbox_id' => $inbox->id,
            'max_concurrent' => 5,
            'accepts_new' => true,
        ]);

        return $agent;
    }

    private function makeInbox(string $name): Inbox
    {
        return Inbox::create(['name' => $name, 'channel_type' => Inbox::CHANNEL_WHATSAPP, 'is_active' => true]);
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('contactos.csv', $content);
    }

    private function import(User $user, string $csv)
    {
        return $this->actingAs($user)->post(route('contacts.import.process'), ['file' => $this->csv($csv)]);
    }

    public function test_import_returns_404_when_contacts_integration_is_disabled(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['contacts.view', 'contacts.update']);

        Setting::set('contacts.integration_enabled', '0', 'integrations');

        try {
            $this->import($user, "name,email\nAlguien,alguien@example.test\n")->assertNotFound();

            $this->assertDatabaseMissing('helpdesk_customers', ['email' => 'alguien@example.test'], 'helpdesk');
        } finally {
            Setting::set('contacts.integration_enabled', '1', 'integrations');
        }
    }

    public function test_import_cannot_modify_a_contact_of_another_inbox(): void
    {
        $inboxA = $this->makeInbox('Inbox A');
        $inboxB = $this->makeInbox('Inbox B');
        $agent = $this->makeRestrictedAgent($inboxA);

        $foreign = Customer::factory()->create([
            'name' => 'Nombre Original',
            'email' => 'ajeno@example.test',
        ]);
        Conversation::factory()->create(['customer_id' => $foreign->id, 'inbox_id' => $inboxB->id]);

        $this->import($agent, "name,email\nNombre Pisado,ajeno@example.test\n")->assertRedirect();

        // Ni se modifica el contacto ajeno ni se duplica el email.
        $this->assertSame('Nombre Original', $foreign->fresh()->name);
        $this->assertSame(1, Customer::query()->where('email', 'ajeno@example.test')->count());
    }

    public function test_import_updates_a_contact_within_the_agent_scope(): void
    {
        $inboxA = $this->makeInbox('Inbox A');
        $agent = $this->makeRestrictedAgent($inboxA);

        $mine = Customer::factory()->create([
            'name' => 'Nombre Antiguo',
            'email' => 'mio@example.test',
        ]);
        Conversation::factory()->create(['customer_id' => $mine->id, 'inbox_id' => $inboxA->id]);

        $this->import($agent, "name,email\nNombre Nuevo,mio@example.test\n")->assertRedirect();

        $this->assertSame('Nombre Nuevo', $mine->fresh()->name);
    }

    public function test_imported_new_contact_is_associated_to_the_agent_inboxes_and_visible(): void
    {
        $inboxA = $this->makeInbox('Inbox A');
        $agent = $this->makeRestrictedAgent($inboxA);

        $this->import($agent, "name,email\nContacto Importado,nuevo@example.test\n")->assertRedirect();

        $customer = Customer::query()->where('email', 'nuevo@example.test')->first();
        $this->assertNotNull($customer);

        // Asociado a la bandeja del agente vía el pivot...
        $this->assertDatabaseHas('helpdesk_customer_inboxes', [
            'customer_id' => $customer->id,
            'inbox_id' => $inboxA->id,
        ], 'helpdesk');

        // ...y por tanto visible bajo el aislamiento por inbox (antes quedaba
        // invisible: sin conversación no entraba en forAgent()).
        $this->assertTrue(
            Customer::query()->forAgent($agent)->whereKey($customer->id)->exists(),
            'El contacto importado debe ser visible para el agente que lo importó.'
        );

        $this->actingAs($agent)
            ->get('/panel/helpdesk/contacts?q=nuevo@example.test')
            ->assertOk()
            ->assertSee('Contacto Importado');
    }

    public function test_import_skips_rows_with_invalid_email(): void
    {
        $inboxA = $this->makeInbox('Inbox A');
        $agent = $this->makeRestrictedAgent($inboxA);

        $response = $this->import(
            $agent,
            "name,email\nEmail Roto,no-es-un-email\nEmail Valido,valido@example.test\n"
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', fn (string $msg): bool => str_contains($msg, '1 con email inválido'));

        $this->assertDatabaseMissing('helpdesk_customers', ['name' => 'Email Roto'], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_customers', ['email' => 'valido@example.test'], 'helpdesk');
    }

    public function test_manager_import_still_updates_contacts_of_any_inbox(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['contacts.view', 'contacts.update', 'helpdesk.manage']);

        $customer = Customer::factory()->create([
            'name' => 'Antes',
            'email' => 'global@example.test',
        ]);

        $this->import($manager, "name,email\nDespues,global@example.test\n")->assertRedirect();

        $this->assertSame('Despues', $customer->fresh()->name);
    }
}
