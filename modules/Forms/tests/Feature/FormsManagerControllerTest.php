<?php

namespace Modules\Forms\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Models\Form;
use Modules\HelpdeskTickets\Database\Factories\TicketCategoryFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FormsManagerControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.settings', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_access_the_manage_screen(): void
    {
        $this->get('/panel/forms/manage')->assertRedirect();
    }

    public function test_user_with_only_view_permission_cannot_manage(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view']);

        $this->actingAs($user)->get('/panel/forms/manage')->assertForbidden();
    }

    public function test_user_with_settings_permission_can_create_a_form(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.view', 'helpdesk.tickets.settings']);
        $category = TicketCategoryFactory::new()->create(['slug' => 'contacto-general']);

        $response = $this->actingAs($user)->post('/panel/forms/manage', [
            'name' => 'Contacto general',
            'form_key' => 'contact',
            'category_id' => $category->id,
            'active' => '1',
        ]);

        $response->assertRedirect(route('forms.manage.index'));
        $this->assertDatabaseHas('helpdesk_forms', [
            'form_key' => 'contact',
            'category_id' => $category->id,
            'active' => 1,
        ], 'helpdesk');
    }

    public function test_form_key_must_be_unique(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.settings']);
        Form::create(['form_key' => 'contact', 'name' => 'Existing', 'active' => true]);

        $response = $this->actingAs($user)->post('/panel/forms/manage', [
            'name' => 'Duplicado',
            'form_key' => 'contact',
            'active' => '1',
        ]);

        $response->assertSessionHasErrors('form_key');
        $this->assertSame(1, Form::on('helpdesk')->where('form_key', 'contact')->count());
    }

    public function test_user_can_update_a_form(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.settings']);
        $form = Form::create(['form_key' => 'contact', 'name' => 'Old name', 'active' => true]);
        $category = TicketCategoryFactory::new()->create(['slug' => 'nueva-categoria']);

        $response = $this->actingAs($user)->put("/panel/forms/manage/{$form->id}", [
            'name' => 'New name',
            'form_key' => 'contact',
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
        $user = $this->makeUser(['helpdesk.tickets.settings']);
        $form = Form::create(['form_key' => 'contact', 'name' => 'Contact', 'active' => true]);

        $this->actingAs($user)->post("/panel/forms/manage/{$form->id}/toggle")->assertRedirect();

        $this->assertFalse($form->refresh()->active);
    }

    public function test_destroy_removes_the_form(): void
    {
        $user = $this->makeUser(['helpdesk.tickets.settings']);
        $form = Form::create(['form_key' => 'contact', 'name' => 'Contact', 'active' => true]);

        $this->actingAs($user)->delete("/panel/forms/manage/{$form->id}")->assertRedirect();

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
