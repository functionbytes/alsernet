<?php

namespace Modules\Page\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageVersion;
use Tests\TestCase;

class VersioningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();

        // Act as this user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_creates_initial_version_when_page_is_created()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Initial content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        // Assert that a version was created
        $this->assertTrue($page->hasVersions());
        $this->assertEquals(1, $page->getTotalVersions());
        $this->assertEquals(1, $page->getCurrentVersionNumber());
    }

    /** @test */
    public function it_creates_version_when_page_is_updated()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Initial content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        $initialVersionCount = $page->getTotalVersions();

        // Update the page
        $page->update([
            'title' => 'Updated Test Page',
            'content' => 'Updated content',
        ]);

        // Assert that a new version was created
        $this->assertEquals($initialVersionCount + 1, $page->fresh()->getTotalVersions());
    }

    /** @test */
    public function it_can_restore_previous_version()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Original Title',
            'slug' => 'test-page',
            'content' => 'Original content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        // Update the page
        $page->update([
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        // Get the first version
        $firstVersion = $page->versions()->oldest()->first();

        // Restore the first version
        $page->restoreVersion($firstVersion->id);

        // Assert that the page was restored
        $this->assertEquals('Original Title', $page->fresh()->title);
        $this->assertEquals('Original content', $page->fresh()->content);
    }

    /** @test */
    public function it_can_compare_two_versions()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Version 1',
            'slug' => 'test-page',
            'content' => 'Content 1',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        // Update to create version 2
        $page->update([
            'title' => 'Version 2',
            'content' => 'Content 2',
        ]);

        $versions = $page->versions()->oldest()->get();
        $comparison = $page->compareVersions($versions[0]->id, $versions[1]->id);

        // Assert comparison results
        $this->assertTrue($comparison['has_changes']);
        $this->assertTrue($comparison['differences']['title']['changed']);
        $this->assertTrue($comparison['differences']['content']['changed']);
        $this->assertEquals('Version 1', $comparison['differences']['title']['old_value']);
        $this->assertEquals('Version 2', $comparison['differences']['title']['new_value']);
    }

    /** @test */
    public function it_can_get_version_history()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        // Create multiple versions
        for ($i = 1; $i <= 5; $i++) {
            $page->update(['content' => "Content version $i"]);
        }

        // Get version history
        $history = $page->getVersionHistory();

        // Assert we have all versions
        $this->assertGreaterThanOrEqual(5, $history->count());
    }

    /** @test */
    public function it_can_manually_create_version()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Content',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);

        $initialCount = $page->getTotalVersions();

        // Manually create a version
        $version = $page->createVersion($this->user->id);

        // Assert version was created
        $this->assertInstanceOf(PageVersion::class, $version);
        $this->assertEquals($initialCount + 1, $page->fresh()->getTotalVersions());
    }

    /** @test */
    public function it_tracks_version_metadata_correctly()
    {
        // Create a page
        $page = Page::create([
            'title' => 'Test Page',
            'slug' => 'test-page',
            'content' => 'Test content',
            'description' => 'Test description',
            'template' => 'default',
            'status' => 'draft',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
            'user_id' => $this->user->id,
        ]);

        $version = $page->versions()->first();

        // Assert all fields are tracked
        $this->assertEquals('Test Page', $version->title);
        $this->assertEquals('Test content', $version->content);
        $this->assertEquals('Test description', $version->description);
        $this->assertEquals('default', $version->template);
        $this->assertEquals('draft', $version->status);
        $this->assertEquals('SEO Title', $version->seo_title);
        $this->assertEquals('SEO Description', $version->seo_description);
    }
}
