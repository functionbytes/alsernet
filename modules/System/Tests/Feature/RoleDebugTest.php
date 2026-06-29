<?php

namespace Modules\System\Tests\Feature;

use Modules\System\Tests\TestCase;

class RoleDebugTest extends TestCase
{
    public function test_administrative_user_has_role_assigned(): void
    {
        $user = $this->createUserWithRole('administrative');

        $this->assertTrue($user->hasRole('administrative'), 'User does not have administrative role');
        $this->assertTrue(
            $user->hasAnyRole(['super-settings', 'administrative', 'manager']),
            'User fails hasAnyRole check. Roles: '.$user->getRoleNames()->implode(', ')
        );
    }

    public function test_administrative_user_passes_settings_route(): void
    {
        $user = $this->createUserWithRole('administrative');

        // settings middleware GET route — should always pass for administrative
        $response = $this->actingAs($user)
            ->getJson(route('settings.system.index'));

        $this->assertNotEquals(403, $response->status(),
            'User blocked from GET route. Roles: '.$user->getRoleNames()->implode(', ')
        );
    }
}
