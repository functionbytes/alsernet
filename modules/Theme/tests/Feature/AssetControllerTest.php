<?php

namespace Modules\Theme\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - RefreshDatabase la
    // migro-fresh por un fallo de force="true" en phpunit.xml (incidente
    // 29-ago-2026) - nunca usar RefreshDatabase en este proyecto.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    public function test_path_traversal_attempt_returns_404(): void
    {
        $response = $this->get('/theme-asset/../../../etc/passwd');

        $this->assertContains($response->status(), [404, 400, 403]);
    }

    public function test_non_existing_asset_returns_404(): void
    {
        $response = $this->get('/theme-asset/nonexistent-file-'.uniqid().'.css');

        $this->assertContains($response->status(), [404, 400]);
    }
}
