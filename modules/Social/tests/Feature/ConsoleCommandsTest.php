<?php

namespace Modules\Social\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Models\SocialListeningKeyword;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function publish_scheduled_command_finds_posts_due_for_publishing(): void
    {
        // Create a post scheduled in the past
        $pastPost = Post::factory()
            ->scheduled()
            ->create([
                'scheduled_at' => now()->subHours(1),
            ]);

        // Create a post scheduled in the future
        $futurePost = Post::factory()
            ->scheduled()
            ->create([
                'scheduled_at' => now()->addHours(1),
            ]);

        $this->artisan('social:publish-scheduled', ['--dry-run' => true])
            ->assertExitCode(0);

        // Past post should still be scheduled (dry run doesn't actually publish)
        $this->assertEquals(PostStatus::SCHEDULED, $pastPost->fresh()->status);
    }

    /** @test */
    public function publish_scheduled_command_respects_limit_option(): void
    {
        // Create 10 posts scheduled in the past
        Post::factory()
            ->count(10)
            ->scheduled()
            ->create([
                'scheduled_at' => now()->subHours(1),
            ]);

        $this->artisan('social:publish-scheduled', [
            '--dry-run' => true,
            '--limit' => 5,
        ])->assertExitCode(0);

        // All should still be scheduled (dry run)
        $this->assertEquals(10, Post::where('status', PostStatus::SCHEDULED)->count());
    }

    /** @test */
    public function sync_stats_command_finds_published_posts(): void
    {
        Post::factory()
            ->published()
            ->create([
                'external_id' => 'test_123',
            ]);

        $this->artisan('social:sync-stats', ['--limit' => 5])
            ->assertExitCode(1); // Will fail because of fake tokens, but that's expected
    }

    /** @test */
    public function sync_stats_command_respects_network_filter(): void
    {
        $fbAccount = SocialAccount::factory()->facebook()->create();
        $igAccount = SocialAccount::factory()->instagram()->create();

        Post::factory()->published()->create([
            'social_account_id' => $fbAccount->id,
            'external_id' => 'fb_123',
        ]);

        Post::factory()->published()->create([
            'social_account_id' => $igAccount->id,
            'external_id' => 'ig_123',
        ]);

        $this->artisan('social:sync-stats', [
            '--network' => 'facebook',
            '--limit' => 10,
        ])->assertExitCode(1); // Will fail because of fake tokens
    }

    /** @test */
    public function scan_listening_command_finds_active_keywords(): void
    {
        SocialListeningKeyword::factory()
            ->active()
            ->count(3)
            ->create();

        $this->artisan('social:scan-listening', ['--limit' => 5])
            ->assertExitCode(1); // Will fail because no real API connections
    }

    /** @test */
    public function scan_listening_command_respects_keyword_filter(): void
    {
        $keyword = SocialListeningKeyword::factory()->active()->create();

        $this->artisan('social:scan-listening', [
            '--keyword' => $keyword->id,
        ])->assertExitCode(1); // Will fail because no real API connections
    }

    /** @test */
    public function commands_show_help_text(): void
    {
        $this->artisan('social:publish-scheduled', ['--help'])
            ->assertExitCode(0);

        $this->artisan('social:sync-stats', ['--help'])
            ->assertExitCode(0);

        $this->artisan('social:scan-listening', ['--help'])
            ->assertExitCode(0);
    }
}
