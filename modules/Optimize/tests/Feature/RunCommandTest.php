<?php

namespace Modules\Optimize\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RunCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_command_rejects_unknown_command(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('settings.optimize.run-command'), [
                'command' => 'rm:-rf',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_run_command_executes_whitelisted_command(): void
    {
        $user = User::factory()->create();
        Artisan::shouldReceive('call')->once()
            ->withArgs(fn ($cmd, $params) => $cmd === 'optimize:enable-all' && ($params['--ttl'] ?? null) === 3600)
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Activadas: 15');

        $res = $this->actingAs($user)
            ->postJson(route('settings.optimize.run-command'), [
                'command' => 'optimize:enable-all',
                'ttl' => '3600',
            ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('output', 'Activadas: 15');
    }

    public function test_run_command_sanitizes_slug_to_alphanumeric(): void
    {
        $user = User::factory()->create();
        Artisan::shouldReceive('call')->once()
            ->withArgs(function ($cmd, $params) {
                return $cmd === 'theme:audit-a11y' && ($params['slug'] ?? null) === 'cai';
            })
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        $this->actingAs($user)
            ->postJson(route('settings.optimize.run-command'), [
                'command' => 'theme:audit-a11y',
                'slug' => 'cai;rm -rf /',
            ])
            ->assertOk();
    }

    public function test_run_command_clamps_quality_to_0_100(): void
    {
        $user = User::factory()->create();
        Artisan::shouldReceive('call')->once()
            ->withArgs(function ($cmd, $params) {
                return $cmd === 'media:convert-webp' && ($params['--quality'] ?? null) === 100;
            })
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        $this->actingAs($user)
            ->postJson(route('settings.optimize.run-command'), [
                'command' => 'media:convert-webp',
                'quality' => '9999',
            ])
            ->assertOk();
    }
}
