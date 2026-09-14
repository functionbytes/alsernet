<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Antes de este fix, /panel/documents solo exigia ['web','auth'] — cualquier
 * usuario autenticado (agente, portal, etc.) podia listar/ver/gestionar
 * cualquier documento (DNI, licencias de armas) sin ningun chequeo de rol.
 */
class DocumentsPanelAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_documents_panel(): void
    {
        $this->get(route('documents.index'))->assertRedirect();
    }

    public function test_authenticated_user_without_role_cannot_access_documents_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertForbidden();
    }

    public function test_authenticated_user_without_role_cannot_access_pending_documents(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('documents.pending'))
            ->assertForbidden();
    }

    public function test_supervisor_can_access_documents_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk();
    }

    public function test_super_admin_can_access_documents_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk();
    }
}
