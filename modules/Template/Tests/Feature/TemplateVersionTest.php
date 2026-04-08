<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Template\Models\Template;
use Modules\Template\Models\TemplateVersion;
use Modules\Template\Services\TemplateService;
use Tests\TestCase;

class TemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
    }

    // -------------------------------------------------------------------------
    // Version creation
    // -------------------------------------------------------------------------

    public function test_creating_template_creates_version_v1(): void
    {
        $service = app(TemplateService::class);

        $template = $service->create([
            'name' => 'New Template',
            'slug' => 'new-template',
            'content' => '<p>Hello</p>',
            'status' => 'inactive',
        ]);

        $version = TemplateVersion::where('template_id', $template->id)->first();

        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version);
    }

    public function test_updating_template_creates_new_version(): void
    {
        $service = app(TemplateService::class);

        $template = Template::factory()->create(['content' => '<p>Original</p>']);

        $countBefore = TemplateVersion::where('template_id', $template->id)->count();

        $service->update($template, [
            'name' => $template->name,
            'content' => '<p>Updated content</p>',
        ]);

        $countAfter = TemplateVersion::where('template_id', $template->id)->count();

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    public function test_restore_version_reverts_content(): void
    {
        $service = app(TemplateService::class);

        $template = Template::factory()->create(['content' => '<p>Original content</p>']);

        $snapshot = $template->createVersion();

        $template->update(['content' => '<p>New content after update</p>']);

        $this->assertEquals('<p>New content after update</p>', $template->fresh()->content);

        $service->restoreVersion($snapshot);

        $this->assertEquals('<p>Original content</p>', $template->fresh()->content);
    }

    public function test_template_soft_delete_preserves_versions(): void
    {
        $template = Template::factory()->inactive()->create();

        $template->createVersion();
        $template->createVersion();

        $template->delete();

        $this->assertSoftDeleted('templates', ['id' => $template->id]);

        // Versions must still be queryable even after the template is soft-deleted
        $versionCount = TemplateVersion::where('template_id', $template->id)->count();

        $this->assertGreaterThan(0, $versionCount);
    }

    public function test_version_list_ordered_by_version_desc(): void
    {
        $template = Template::factory()->create();

        $template->createVersion();
        $template->createVersion();
        $template->createVersion();

        $versions = $template->versions()
            ->orderByDesc('version')
            ->pluck('version')
            ->toArray();

        $sorted = $versions;
        rsort($sorted);

        $this->assertEquals($sorted, $versions);
    }
}
