<?php

namespace Modules\Forms\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Models\AlsernetForm;
use Modules\HelpdeskTickets\Database\Factories\TicketCategoryFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FormsManagerControllerTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es la conexión POR DEFECTO de la app: sin ella aquí, dos cosas
    // fallan. Las reglas de validación que cualifican la base a mano
    // (Rule::unique('helpdesk.helpdesk_forms', …)) consultan por la conexión
    // por defecto y no ven las filas escritas dentro de la transacción de
    // 'helpdesk'; y cualquier escritura que el test haga por 'mysql' no se
    // revierte al terminar, quedándose en la BD real.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    /**
     * form_key único por ejecución. La BD de test es la misma que la de
     * desarrollo y 'contact' ya existe como formulario real: fijarlo a mano
     * hacía que estos tests murieran con UniqueConstraintViolation antes de
     * llegar a comprobar nada.
     */
    private string $formKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formKey = 'test_form_'.uniqid();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_access_the_manage_screen(): void
    {
        $this->get(route('forms.manage.index'))->assertRedirect();
    }

    public function test_user_with_only_view_permission_cannot_manage(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view']);

        $this->actingAs($user)->get(route('forms.manage.index'))->assertForbidden();
    }

    public function test_user_with_settings_permission_can_create_a_form(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.settings']);
        // Slug único por ejecución: la BD de test es la misma que la de
        // desarrollo y 'contacto-general' ya existe como categoría real, así
        // que fijarlo a mano reventaba con UniqueConstraintViolation.
        $category = TicketCategoryFactory::new()->create(['slug' => 'test-contacto-'.uniqid()]);

        $response = $this->actingAs($user)->post(route('forms.manage.store'), [
            'name' => 'Contacto general',
            'form_key' => $this->formKey,
            'category_id' => $category->id,
            'active' => '1',
        ]);

        $response->assertRedirect(route('forms.manage.index'));
        $this->assertDatabaseHas('helpdesk_forms', [
            'form_key' => $this->formKey,
            'category_id' => $category->id,
            'active' => 1,
        ], 'helpdesk');
    }

    public function test_form_key_must_be_unique(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.settings']);
        AlsernetForm::create(['form_key' => $this->formKey, 'name' => 'Existing', 'active' => true]);

        $response = $this->actingAs($user)->post(route('forms.manage.store'), [
            'name' => 'Duplicado',
            'form_key' => $this->formKey,
            'active' => '1',
        ]);

        $response->assertSessionHasErrors('form_key');
        $this->assertSame(1, AlsernetForm::on('helpdesk')->where('form_key', $this->formKey)->count());
    }

    public function test_user_can_update_a_form(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.settings']);
        $form = AlsernetForm::create(['form_key' => $this->formKey, 'name' => 'Old name', 'active' => true]);
        $category = TicketCategoryFactory::new()->create(['slug' => 'test-nueva-'.uniqid()]);

        $response = $this->actingAs($user)->put(route('forms.manage.update', $form), [
            'name' => 'New name',
            'form_key' => $this->formKey,
            'category_id' => $category->id,
            'active' => '0',
        ]);

        $response->assertRedirect(route('forms.manage.index'));
        $form->refresh();
        $this->assertSame('New name', $form->name);
        $this->assertSame($category->id, $form->category_id);
        $this->assertFalse($form->active);
    }

    public function test_toggle_flips_active_state(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.settings']);
        $form = AlsernetForm::create(['form_key' => $this->formKey, 'name' => 'Contact', 'active' => true]);

        $this->actingAs($user)->post(route('forms.manage.toggle', $form))->assertRedirect();

        $this->assertFalse($form->refresh()->active);
    }

    public function test_destroy_removes_the_form(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.settings']);
        $form = AlsernetForm::create(['form_key' => $this->formKey, 'name' => 'Contact', 'active' => true]);

        $this->actingAs($user)->delete(route('forms.manage.destroy', $form))->assertRedirect();

        $this->assertDatabaseMissing('helpdesk_forms', ['id' => $form->id], 'helpdesk');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<int, string>  $permissions
     */
    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
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
}
