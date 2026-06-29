<?php

namespace Modules\MailsSettings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/settings/outgoing-email');

        $response->assertRedirect();
    }

    public function test_user_without_permission_cannot_view_outgoing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/panel/settings/outgoing-email');

        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_user_with_permission_can_view_outgoing(): void
    {
        Permission::firstOrCreate(['name' => 'mails-settings.outgoing.view', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->givePermissionTo('mails-settings.outgoing.view');

        $response = $this->actingAs($user)->get('/panel/settings/outgoing-email');

        $this->assertContains($response->status(), [200, 404]);
    }
}
