<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Models\Page;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageLockIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'page.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'page.view', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['page.update', 'page.view']);

        $this->page = Page::factory()->create();
    }

    public function test_release_without_existing_lock_returns_200_with_released_false(): void
    {
        $res = $this->actingAs($this->user)
            ->deleteJson(route('pages.lock.release', $this->page));

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('released', false);
    }

    public function test_release_after_acquire_returns_released_true_then_false(): void
    {
        // Acquire
        $this->actingAs($this->user)
            ->postJson(route('pages.lock.acquire', $this->page))
            ->assertOk();

        // First release: had a lock → released=true
        $first = $this->actingAs($this->user)
            ->deleteJson(route('pages.lock.release', $this->page));
        $first->assertOk()->assertJsonPath('released', true);

        // Second release: no lock anymore → released=false but still 200
        $second = $this->actingAs($this->user)
            ->deleteJson(route('pages.lock.release', $this->page));
        $second->assertOk()->assertJsonPath('released', false);
    }
}
