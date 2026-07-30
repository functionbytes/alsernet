<?php

namespace Modules\Theme\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetControllerTest extends TestCase
{
    use RefreshDatabase;

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
