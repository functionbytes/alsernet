<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskEmailActivity\Enums\SuppressionReason;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmailSuppressionControllerTest extends TestCase
{
    use DatabaseTransactions;

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
        $this->get(route('settings.helpdeskemailactivity.suppressions.index'))->assertRedirect();
    }

    public function test_index_requires_settings_view_permission(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('settings.helpdeskemailactivity.suppressions.index'))
            ->assertForbidden();
    }

    public function test_index_renders_for_authorized_user(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdeskemailactivity.suppressions.index'))
            ->assertOk()
            ->assertSee('Lista de supresión');
    }

    public function test_manager_can_manually_add_a_suppression(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.suppressions.store'), [
                'email' => 'Manual@Example.Test',
                'module' => '',
                'reason' => 'manual',
                'notes' => 'Pidió baja por teléfono',
            ])
            ->assertRedirect(route('settings.helpdeskemailactivity.suppressions.index'));

        // El email se normaliza a minúsculas al guardar (ver EmailSuppression::booted()).
        $suppression = EmailSuppression::where('email', 'manual@example.test')->first();

        $this->assertNotNull($suppression);
        $this->assertSame('', $suppression->module);
        $this->assertSame(SuppressionReason::Manual, $suppression->reason);
        $this->assertSame($this->manager->id, $suppression->causer_id);
        $this->assertSame('Pidió baja por teléfono', $suppression->notes);
    }

    public function test_store_requires_settings_update_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdeskemailactivity.settings.view');

        $this->actingAs($viewer)
            ->post(route('settings.helpdeskemailactivity.suppressions.store'), [
                'email' => 'x@example.test', 'reason' => 'manual',
            ])
            ->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.suppressions.store'), [])
            ->assertSessionHasErrors(['email', 'reason']);
    }

    public function test_store_validates_email_format(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.suppressions.store'), [
                'email' => 'no-es-un-email', 'reason' => 'manual',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_repeated_email_and_module_updates_instead_of_duplicating(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.suppressions.store'), [
                'email' => 'dup@example.test', 'module' => '', 'reason' => 'manual',
            ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdeskemailactivity.suppressions.store'), [
                'email' => 'dup@example.test', 'module' => '', 'reason' => 'unsubscribed',
            ]);

        $this->assertSame(1, EmailSuppression::where('email', 'dup@example.test')->count());
        $this->assertSame(SuppressionReason::Unsubscribed, EmailSuppression::where('email', 'dup@example.test')->first()->reason);
    }

    public function test_manager_can_remove_a_suppression(): void
    {
        $suppression = EmailSuppression::create(['email' => 'toremove@example.test', 'reason' => SuppressionReason::Manual]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdeskemailactivity.suppressions.destroy', $suppression))
            ->assertRedirect(route('settings.helpdeskemailactivity.suppressions.index'));

        $this->assertNull(EmailSuppression::find($suppression->id));
    }

    public function test_destroy_requires_settings_update_permission(): void
    {
        $suppression = EmailSuppression::create(['email' => 'protected@example.test', 'reason' => SuppressionReason::Manual]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('helpdeskemailactivity.settings.view');

        $this->actingAs($viewer)
            ->delete(route('settings.helpdeskemailactivity.suppressions.destroy', $suppression))
            ->assertForbidden();
    }
}
