<?php

namespace Modules\Pulse\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class PulseSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Module::find('Pulse')?->isEnabled()) {
            $this->markTestSkipped('Pulse module is disabled in modules_statuses.json');
        }
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/settings/pulse');

        $response->assertRedirect();
    }
}
