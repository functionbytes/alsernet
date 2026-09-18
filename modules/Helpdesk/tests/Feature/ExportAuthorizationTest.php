<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Modules\Helpdesk\Tests\HelpdeskTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Regresión: los exports CSV (conversaciones/contactos/CSAT) contienen PII y
 * exigen el permiso helpdesk.exports.create — tanto en el middleware `can:`
 * del grupo de rutas como en el authorize() de cada Form Request. Antes las
 * rutas solo llevaban throttle.
 */
class ExportAuthorizationTest extends HelpdeskTestCase
{
    /** @return array<string, array{string}> */
    public static function exportRoutes(): array
    {
        return [
            'conversations' => ['manager.helpdesk.exports.conversations'],
            'customers' => ['manager.helpdesk.exports.customers'],
            'csat' => ['manager.helpdesk.exports.csat'],
        ];
    }

    #[DataProvider('exportRoutes')]
    public function test_user_without_permission_cannot_export(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertForbidden();
    }

    #[DataProvider('exportRoutes')]
    public function test_authorized_user_can_export(string $routeName): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route($routeName))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}
