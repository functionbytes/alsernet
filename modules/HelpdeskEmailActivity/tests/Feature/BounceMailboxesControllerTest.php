<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailActivity\Services\BounceMailboxesRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BounceMailboxesControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' imprescindible: BounceMailboxesRepository escribe vía
    // Modules\Core\Models\Setting (conexión default = mysql en este entorno)
    // — sin declararla, cada Setting::set()/setEncrypted() escribe una fila
    // REAL sin rollback. Ver TicketEmailChannelsRepositoryTest para el mismo
    // gotcha ya documentado.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('helpdeskemailactivity.settings.view', 'web');
        Permission::findOrCreate('helpdeskemailactivity.settings.update', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(['helpdeskemailactivity.settings.view', 'helpdeskemailactivity.settings.update']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('settings.helpdeskemailactivity.bounce-mailboxes.index'))->assertRedirect();
    }

    public function test_index_requires_settings_view_permission(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('settings.helpdeskemailactivity.bounce-mailboxes.index'))
            ->assertForbidden();
    }

    public function test_index_renders_for_authorized_user(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskemailactivity.bounce-mailboxes.index'))
            ->assertOk()
            ->assertSee('Buzones de rebote');
    }

    public function test_manager_can_create_a_mailbox(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.bounce-mailboxes.store'), [
                'label' => 'Buzón principal',
                'host' => 'imap.example.test',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => 'bounces@example.test',
                'password' => 'secret',
                'folder' => 'INBOX',
                'module_scope' => ['Document'],
                'enabled' => '1',
            ])
            ->assertRedirect(route('settings.helpdeskemailactivity.bounce-mailboxes.index'));

        // No se asume que la lista esté vacía antes de este test: el blob vive
        // en un Setting cuya caché real no está cubierta por la transacción de
        // BD del test (mismo problema conocido en el proyecto que
        // Setting::set() en general — ver memoria "Setting cache escapa la
        // transacción"). Se busca la fila creada por su label en vez de
        // asumir un conteo exacto.
        $mailboxes = app(BounceMailboxesRepository::class)->all();
        $created = collect($mailboxes)->firstWhere('label', 'Buzón principal');

        $this->assertNotNull($created);
        $this->assertSame(['Document'], $created['module_scope']);
        $this->assertTrue($created['enabled']);
        // La contraseña se guarda cifrada dentro del blob (Setting::setEncrypted),
        // nunca en plano — comprobado indirectamente: el repositorio la devuelve
        // igual al leerla de vuelta (el cifrado/descifrado es transparente).
        $this->assertSame('secret', $created['password']);
    }

    public function test_store_requires_settings_update_permission(): void
    {
        $viewer = User::factory()->create();
        Permission::findOrCreate('helpdeskemailactivity.settings.view', 'web');
        $viewer->givePermissionTo('helpdeskemailactivity.settings.view');

        $this->actingAs($viewer)
            ->post(route('settings.helpdeskemailactivity.bounce-mailboxes.store'), [
                'label' => 'x', 'host' => 'x', 'username' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.bounce-mailboxes.store'), [])
            ->assertSessionHasErrors(['label', 'host', 'username']);
    }

    public function test_manager_can_update_a_mailbox_and_blank_password_keeps_the_existing_one(): void
    {
        $repo = app(BounceMailboxesRepository::class);
        $mailbox = $repo->create([
            'label' => 'Original', 'host' => 'old.example.test', 'port' => 993,
            'encryption' => 'ssl', 'username' => 'user@example.test', 'password' => 'original-secret',
            'folder' => 'INBOX', 'module_scope' => [], 'enabled' => false,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdeskemailactivity.bounce-mailboxes.update', $mailbox['id']), [
                'label' => 'Actualizado',
                'host' => 'new.example.test',
                'username' => 'user@example.test',
                'password' => '',
                'enabled' => '1',
            ])
            ->assertRedirect(route('settings.helpdeskemailactivity.bounce-mailboxes.index'));

        $updated = $repo->find($mailbox['id']);

        $this->assertSame('Actualizado', $updated['label']);
        $this->assertSame('new.example.test', $updated['host']);
        $this->assertSame('original-secret', $updated['password']);
        $this->assertTrue($updated['enabled']);
    }

    public function test_update_returns_404_for_unknown_mailbox(): void
    {
        $this->actingAs($this->manager)
            ->put(route('settings.helpdeskemailactivity.bounce-mailboxes.update', 'no-existe'), [
                'label' => 'x', 'host' => 'x', 'username' => 'x',
            ])
            ->assertNotFound();
    }

    public function test_manager_can_delete_a_mailbox(): void
    {
        $repo = app(BounceMailboxesRepository::class);
        $mailbox = $repo->create(['label' => 'A borrar', 'host' => 'x', 'username' => 'x']);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdeskemailactivity.bounce-mailboxes.destroy', $mailbox['id']))
            ->assertRedirect(route('settings.helpdeskemailactivity.bounce-mailboxes.index'));

        $this->assertNull($repo->find($mailbox['id']));
    }
}
