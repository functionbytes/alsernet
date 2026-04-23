<?php

namespace Modules\Page\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Authentication guard
    // -------------------------------------------------------------------------

    public function test_guest_cannot_access_pages_index(): void
    {
        $this->get(route('pages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_create_form(): void
    {
        $this->get(route('pages.create'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // GET /pages  (index)
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_view_pages_index(): void
    {
        Page::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.index'))
            ->assertOk()
            ->assertViewIs('page::pages.pages.index')
            ->assertViewHas('pages');
    }

    public function test_index_returns_paginated_list(): void
    {
        Page::factory()->count(5)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.index'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // GET /pages/create
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_view_create_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('pages.create'))
            ->assertOk()
            ->assertViewIs('page::pages.pages.create')
            ->assertViewHas(['page', 'templates', 'statuses']);
    }

    // -------------------------------------------------------------------------
    // POST /pages  (store)
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_create_a_page(): void
    {
        $payload = [
            'title' => 'My New Page',
            'status' => PageStatus::Draft->value,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('pages.store'), $payload);

        $page = Page::where('title', 'My New Page')->first();

        $this->assertNotNull($page);

        $response->assertRedirect(route('pages.edit', $page->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'title' => 'My New Page',
            'status' => PageStatus::Draft->value,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_auto_generates_slug_from_title(): void
    {
        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'title' => 'Auto Slug Page',
                'status' => PageStatus::Draft->value,
            ]);

        $this->assertDatabaseHas('pages', ['slug' => 'auto-slug-page']);
    }

    public function test_store_sets_published_at_when_publishing(): void
    {
        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'title' => 'Published Now',
                'status' => PageStatus::Published->value,
            ]);

        $page = Page::where('title', 'Published Now')->first();

        $this->assertNotNull($page);
        $this->assertNotNull($page->published_at);
        $this->assertEquals(PageStatus::Published->value, $page->status);
    }

    // -------------------------------------------------------------------------
    // POST /pages — validation failures
    // -------------------------------------------------------------------------

    public function test_store_fails_without_title(): void
    {
        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'status' => PageStatus::Draft->value,
            ])
            ->assertSessionHasErrors(['title']);

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_store_fails_without_status(): void
    {
        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'title' => 'No Status Page',
            ])
            ->assertSessionHasErrors(['status']);
    }

    public function test_store_fails_with_invalid_status(): void
    {
        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'title' => 'Bad Status',
                'status' => 'invalid-status',
            ])
            ->assertSessionHasErrors(['status']);
    }

    public function test_store_fails_with_invalid_slug_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'title' => 'Valid Title',
                'slug' => 'Invalid Slug With Spaces',
                'status' => PageStatus::Draft->value,
            ])
            ->assertSessionHasErrors(['slug']);
    }

    public function test_store_fails_with_duplicate_slug(): void
    {
        Page::factory()->create(['slug' => 'duplicate-slug']);

        $this->actingAs($this->user)
            ->post(route('pages.store'), [
                'title' => 'Another Page',
                'slug' => 'duplicate-slug',
                'status' => PageStatus::Draft->value,
            ])
            ->assertSessionHasErrors(['slug']);
    }

    // -------------------------------------------------------------------------
    // GET /pages/{page}/edit
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_view_edit_form(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('pages.edit', $page))
            ->assertOk()
            ->assertViewIs('page::pages.pages.edit')
            ->assertViewHas(['page', 'templates', 'statuses']);
    }

    public function test_guest_cannot_access_edit_form(): void
    {
        $page = Page::factory()->create();

        $this->get(route('pages.edit', $page))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // PUT /pages/{page}  (update)
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_update_a_page(): void
    {
        $page = Page::factory()->draft()->create([
            'title' => 'Original Title',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('pages.update', $page), [
                'title' => 'Updated Title',
                'status' => PageStatus::Draft->value,
                'content' => 'Updated content',
            ])
            ->assertRedirect(route('pages.edit', $page->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_fails_without_title(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->put(route('pages.update', $page), [
                'status' => PageStatus::Draft->value,
            ])
            ->assertSessionHasErrors(['title']);
    }

    public function test_update_fails_without_status(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->put(route('pages.update', $page), [
                'title' => 'Title without status',
            ])
            ->assertSessionHasErrors(['status']);
    }

    // -------------------------------------------------------------------------
    // DELETE /pages/{page}  (destroy — soft delete)
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_soft_delete_a_page(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('pages.destroy', $page))
            ->assertRedirect(route('pages.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    public function test_soft_deleted_page_is_not_in_active_records(): void
    {
        $page = Page::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('pages.destroy', $page));

        $this->assertNull(Page::find($page->id));
        $this->assertNotNull(Page::withTrashed()->find($page->id));
    }

    public function test_guest_cannot_delete_a_page(): void
    {
        $page = Page::factory()->create();

        $this->delete(route('pages.destroy', $page))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'deleted_at' => null]);
    }

    // -------------------------------------------------------------------------
    // POST /pages/{page}/publish
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_publish_a_page(): void
    {
        $page = Page::factory()->draft()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('pages.publish', $page))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => PageStatus::Published->value,
        ]);

        $this->assertNotNull($page->fresh()->published_at);
    }

    public function test_publishing_already_published_page_keeps_published_status(): void
    {
        $page = Page::factory()->published()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('pages.publish', $page))
            ->assertSessionHas('success');

        $this->assertEquals(PageStatus::Published->value, $page->fresh()->status);
    }

    public function test_guest_cannot_publish_a_page(): void
    {
        $page = Page::factory()->draft()->create();

        $this->post(route('pages.publish', $page))
            ->assertRedirect(route('login'));

        $this->assertEquals(PageStatus::Draft->value, $page->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // POST /pages/{page}/unpublish
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_unpublish_a_page(): void
    {
        $page = Page::factory()->published()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('pages.unpublish', $page))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'status' => PageStatus::Draft->value,
        ]);
    }

    public function test_unpublishing_a_draft_page_keeps_draft_status(): void
    {
        $page = Page::factory()->draft()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->post(route('pages.unpublish', $page))
            ->assertSessionHas('success');

        $this->assertEquals(PageStatus::Draft->value, $page->fresh()->status);
    }

    public function test_guest_cannot_unpublish_a_page(): void
    {
        $page = Page::factory()->published()->create();

        $this->post(route('pages.unpublish', $page))
            ->assertRedirect(route('login'));

        $this->assertEquals(PageStatus::Published->value, $page->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // POST /pages/{page}/duplicate
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_duplicate_a_page(): void
    {
        $page = Page::factory()->create([
            'title' => 'Original Page',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('pages.duplicate', $page));

        $copy = Page::where('title', 'Original Page (Copy)')->first();

        $this->assertNotNull($copy);

        $response->assertRedirect(route('pages.edit', $copy->id))
            ->assertSessionHas('success');

        $this->assertEquals(PageStatus::Draft->value, $copy->status);
        $this->assertNull($copy->published_at);
        $this->assertNotEquals($page->id, $copy->id);
        $this->assertNotEquals($page->slug, $copy->slug);
    }

    public function test_duplicate_creates_independent_copy(): void
    {
        $page = Page::factory()->published()->create([
            'title' => 'Source Page',
            'content' => 'Some content',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('pages.duplicate', $page));

        $this->assertDatabaseCount('pages', 2);

        $copy = Page::where('title', 'Source Page (Copy)')->first();
        $this->assertEquals('Some content', $copy->content);
        $this->assertEquals(PageStatus::Draft->value, $copy->status);
    }

    public function test_guest_cannot_duplicate_a_page(): void
    {
        $page = Page::factory()->create();

        $this->post(route('pages.duplicate', $page))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('pages', 1);
    }

    // -------------------------------------------------------------------------
    // Authorization 403 — authenticated user without permission
    // -------------------------------------------------------------------------

    public function test_user_without_page_view_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pages.index'))
            ->assertForbidden();
    }

    public function test_user_without_page_view_permission_cannot_access_edit(): void
    {
        $page = Page::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pages.edit', $page))
            ->assertForbidden();
    }

    public function test_user_without_page_create_permission_cannot_access_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('pages.create'))
            ->assertForbidden();
    }

    public function test_user_without_page_create_permission_cannot_store_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('pages.store'), [
                'title' => 'Unauthorized',
                'status' => PageStatus::Draft->value,
            ])
            ->assertForbidden();
    }

    public function test_user_without_page_update_permission_cannot_update_page(): void
    {
        $page = Page::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('pages.update', $page), [
                'title' => 'Hacked',
                'status' => PageStatus::Draft->value,
            ])
            ->assertForbidden();
    }

    public function test_user_without_page_delete_permission_cannot_delete_page(): void
    {
        $page = Page::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('pages.destroy', $page))
            ->assertForbidden();
    }

    public function test_user_without_page_publish_permission_cannot_publish_page(): void
    {
        $page = Page::factory()->draft()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('pages.publish', $page))
            ->assertForbidden();
    }

    public function test_user_without_page_publish_permission_cannot_unpublish_page(): void
    {
        $page = Page::factory()->published()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('pages.unpublish', $page))
            ->assertForbidden();
    }

    public function test_user_without_page_create_permission_cannot_duplicate_page(): void
    {
        $page = Page::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('pages.duplicate', $page))
            ->assertForbidden();
    }

    public function test_user_without_page_update_permission_cannot_access_bulk_action(): void
    {
        $pages = Page::factory()->count(2)->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('pages.bulk-action'), [
                'action' => 'delete',
                'ids' => $pages->pluck('id')->all(),
            ])
            ->assertForbidden();
    }
}
