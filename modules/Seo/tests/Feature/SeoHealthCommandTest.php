<?php

namespace Modules\Seo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Modules\Seo\Models\Seo404Log;
use Modules\Seo\Tests\TestCase;

class SeoHealthCommandTest extends TestCase
{
    public function test_command_runs_and_returns_exit_code(): void
    {
        $exitCode = Artisan::call('seo:health');
        $output = Artisan::output();

        $this->assertStringContainsString('SEO Health Report', $output);
        $this->assertContains($exitCode, [0, 1, 2], 'exit code must be one of 0/1/2');
    }

    public function test_json_output_is_valid(): void
    {
        Artisan::call('seo:health', ['--json' => true]);
        $output = trim(Artisan::output());

        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('checks', $decoded);
        $this->assertArrayHasKey('generated_at', $decoded);
    }

    public function test_health_http_endpoint_returns_structured_response(): void
    {
        $user = $this->createUser(['Seo.metas.index']);

        $response = $this->actingAs($user)
            ->postJson(route('settings.seo.health.run'));

        $response->assertOk()
            ->assertJsonStructure([
                'ok', 'exit_code', 'generated_at',
                'checks' => [['name', 'status', 'message']],
                'summary' => ['pass', 'warn', 'fail', 'skip'],
            ]);
    }

    public function test_health_endpoint_requires_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson(route('settings.seo.health.run'))
            ->assertForbidden();
    }

    public function test_health_reports_404_warnings_when_volume_is_high(): void
    {
        for ($i = 0; $i < 501; $i++) {
            Seo404Log::create([
                'path' => "/test-{$i}",
                'hit_count' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        Artisan::call('seo:health', ['--json' => true]);
        $output = json_decode(trim(Artisan::output()), true);

        $fourOhFours = collect($output['checks'])->firstWhere('name', '404s');
        $this->assertEquals('warn', $fourOhFours['status']);
    }
}
