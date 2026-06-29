<?php

namespace Modules\Blog\Tests\Feature;

use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostVersion;
use Modules\Blog\Tests\TestCase;

class BlogPostRevisionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Version creation on update
    // -------------------------------------------------------------------------

    public function test_updating_post_creates_version_snapshot(): void
    {
        $admin = $this->createBlogAdmin();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        $versionsBefore = BlogPostVersion::where('blog_post_id', $post->id)->count();

        $this->actingAs($admin)
            ->put(route('blog.posts.update', $post), [
                'title' => 'Titulo actualizado',
                'status' => 'draft',
                'content' => 'Contenido actualizado',
            ]);

        $versionsAfter = BlogPostVersion::where('blog_post_id', $post->id)->count();

        $this->assertGreaterThan($versionsBefore, $versionsAfter);
    }

    // -------------------------------------------------------------------------
    // GET /blog/posts/{post}/versions — version history page
    // -------------------------------------------------------------------------

    public function test_versions_page_shows_history(): void
    {
        $admin = $this->createBlogAdmin();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        // Create two versions manually
        BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Titulo original',
            'slug' => $post->slug,
            'content' => 'Contenido original',
            'version_number' => 1,
        ]);

        BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Titulo version 2',
            'slug' => $post->slug,
            'content' => 'Contenido version 2',
            'version_number' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('blog.posts.versions', $post))
            ->assertOk()
            ->assertViewIs('blog::posts.versions');
    }

    public function test_versions_page_requires_auth(): void
    {
        $post = BlogPost::factory()->draft()->create();

        $this->get(route('blog.posts.versions', $post))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // POST /blog/posts/{post}/versions/{version}/restore — restore version
    // -------------------------------------------------------------------------

    public function test_can_restore_version(): void
    {
        $admin = $this->createBlogAdmin();
        $post = BlogPost::factory()->draft()->create([
            'user_id' => $admin->id,
            'title' => 'Titulo actual',
            'content' => 'Contenido actual',
        ]);

        $version = BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Titulo version antigua',
            'slug' => $post->slug,
            'content' => 'Contenido version antigua',
            'version_number' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('blog.posts.versions.restore', [$post, $version]))
            ->assertRedirect(route('blog.posts.edit', $post));

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'Titulo version antigua',
        ]);
    }

    public function test_restore_creates_backup_version_before_restoring(): void
    {
        $admin = $this->createBlogAdmin();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        $version = BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Version para restaurar',
            'slug' => $post->slug,
            'content' => 'Contenido de la version',
            'version_number' => 1,
        ]);

        $countBefore = BlogPostVersion::where('blog_post_id', $post->id)->count();

        $this->actingAs($admin)
            ->post(route('blog.posts.versions.restore', [$post, $version]));

        // updatePost() always saves a new version snapshot, so count should increase
        $countAfter = BlogPostVersion::where('blog_post_id', $post->id)->count();

        $this->assertGreaterThan($countBefore, $countAfter);
    }

    // -------------------------------------------------------------------------
    // Versions ordered newest first
    // -------------------------------------------------------------------------

    public function test_versions_are_ordered_newest_first(): void
    {
        $admin = $this->createBlogAdmin();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Version 1',
            'slug' => $post->slug,
            'content' => 'v1',
            'version_number' => 1,
        ]);

        BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Version 2',
            'slug' => $post->slug,
            'content' => 'v2',
            'version_number' => 2,
        ]);

        $versions = $post->versions()->get();

        $this->assertEquals(2, $versions->first()->version_number);
        $this->assertEquals(1, $versions->last()->version_number);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_unauthorized_user_cannot_restore_version(): void
    {
        $admin = $this->createBlogAdmin();
        $unauthorized = $this->createUser(); // No blog permissions

        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);
        $version = BlogPostVersion::create([
            'blog_post_id' => $post->id,
            'created_by' => $admin->id,
            'title' => 'Version original',
            'slug' => $post->slug,
            'content' => 'Contenido original',
            'version_number' => 1,
        ]);

        $this->actingAs($unauthorized)
            ->post(route('blog.posts.versions.restore', [$post, $version]))
            ->assertForbidden();
    }
}
