<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Template\Models\Shortcode;
use Tests\TestCase;

class ShortcodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Gate::before(fn () => true);
    }

    // -------------------------------------------------------------------------
    // Authentication guards
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected_to_login_on_index(): void
    {
        Gate::before(fn () => null);

        $this->get(route('settings.shortcodes.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_create(): void
    {
        Gate::before(fn () => null);

        $this->get(route('settings.shortcodes.create'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_store(): void
    {
        Gate::before(fn () => null);

        $this->post(route('settings.shortcodes.store'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_edit(): void
    {
        Gate::before(fn () => null);

        $shortcode = Shortcode::factory()->create();

        $this->get(route('settings.shortcodes.edit', $shortcode))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_update(): void
    {
        Gate::before(fn () => null);

        $shortcode = Shortcode::factory()->create();

        $this->put(route('settings.shortcodes.update', $shortcode))
            ->assertRedirect(route('auth.login'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_destroy(): void
    {
        Gate::before(fn () => null);

        $shortcode = Shortcode::factory()->create();

        $this->delete(route('settings.shortcodes.destroy', $shortcode))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // GET /settings/shortcodes (index)
    // -------------------------------------------------------------------------

    public function test_index_returns_ok_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index'))
            ->assertOk();
    }

    public function test_index_passes_shortcodes_to_view(): void
    {
        Shortcode::factory()->count(3)->create();

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index'))
            ->assertOk()
            ->assertViewHas('shortcodes');
    }

    public function test_index_passes_stats_to_view(): void
    {
        Shortcode::factory()->count(2)->active()->create();
        Shortcode::factory()->count(3)->inactive()->create();
        Shortcode::factory()->active()->withRenderTemplate()->create();

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index'))
            ->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['total'] === 6
                    && $stats['active'] === 3
                    && $stats['inactive'] === 3
                    && $stats['with_render'] >= 1;
            });
    }

    public function test_index_search_filter_by_name(): void
    {
        $matching = Shortcode::factory()->create(['name' => 'Test Shortcode']);
        Shortcode::factory()->create(['name' => 'Other Shortcode']);

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index', ['search' => 'Test']))
            ->assertOk()
            ->assertViewHas('shortcodes', function ($shortcodes) use ($matching) {
                return $shortcodes->contains($matching)
                    && $shortcodes->count() === 1;
            });
    }

    public function test_index_search_filter_by_key(): void
    {
        $matching = Shortcode::factory()->create(['key' => 'unique-key']);
        Shortcode::factory()->create(['key' => 'other-key']);

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index', ['search' => 'unique']))
            ->assertOk()
            ->assertViewHas('shortcodes', function ($shortcodes) use ($matching) {
                return $shortcodes->contains($matching);
            });
    }

    public function test_index_status_filter_active(): void
    {
        Shortcode::factory()->count(2)->active()->create();
        Shortcode::factory()->count(3)->inactive()->create();

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index', ['status' => 'active']))
            ->assertOk()
            ->assertViewHas('shortcodes', function ($shortcodes) {
                return $shortcodes->count() === 2
                    && $shortcodes->every(fn ($s) => $s->is_active);
            });
    }

    public function test_index_status_filter_inactive(): void
    {
        Shortcode::factory()->count(2)->active()->create();
        Shortcode::factory()->count(3)->inactive()->create();

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertViewHas('shortcodes', function ($shortcodes) {
                return $shortcodes->count() === 3
                    && $shortcodes->every(fn ($s) => ! $s->is_active);
            });
    }

    // -------------------------------------------------------------------------
    // GET /settings/shortcodes/create
    // -------------------------------------------------------------------------

    public function test_create_returns_ok_for_authenticated_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.create'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // POST /settings/shortcodes (store)
    // -------------------------------------------------------------------------

    public function test_store_creates_shortcode_with_valid_data(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => 'new-shortcode',
                'name' => 'New Shortcode',
                'description' => 'A test shortcode',
                'shortcode_template' => '[new_shortcode]',
                'is_active' => '1',
            ])
            ->assertRedirect(route('settings.shortcodes.index'));

        $this->assertDatabaseHas('shortcodes', [
            'key' => 'new-shortcode',
            'name' => 'New Shortcode',
            'is_active' => true,
        ]);
    }

    public function test_store_validates_key_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'name' => 'New Shortcode',
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_store_validates_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => 'test-key',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_key_is_unique(): void
    {
        $existing = Shortcode::factory()->create(['key' => 'unique-key']);

        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => $existing->key,
                'name' => 'Different Name',
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_store_validates_key_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => 'Invalid@Key!',
                'name' => 'Test',
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_store_validates_key_max_length(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => str_repeat('a', 101),
                'name' => 'Test',
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_store_validates_name_max_length(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => 'valid-key',
                'name' => str_repeat('a', 151),
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_store_accepts_config_fields_as_json(): void
    {
        $configFields = json_encode([
            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => 'test-shortcode',
                'name' => 'Test',
                'config_fields' => $configFields,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shortcodes', [
            'key' => 'test-shortcode',
        ]);

        $shortcode = Shortcode::where('key', 'test-shortcode')->first();
        $this->assertIsArray($shortcode->config_fields);
        $this->assertEquals('title', $shortcode->config_fields[0]['name']);
    }

    public function test_store_inactive_by_default(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.shortcodes.store'), [
                'key' => 'test-key',
                'name' => 'Test Name',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shortcodes', [
            'key' => 'test-key',
            'is_active' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /settings/shortcodes/{shortcode}/edit
    // -------------------------------------------------------------------------

    public function test_edit_returns_ok_for_existing_shortcode(): void
    {
        $shortcode = Shortcode::factory()->create();

        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.edit', $shortcode))
            ->assertOk()
            ->assertViewHas('shortcode');
    }

    public function test_edit_returns_404_for_nonexistent_shortcode(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.shortcodes.edit', 99999999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // PUT /settings/shortcodes/{shortcode} (update)
    // -------------------------------------------------------------------------

    public function test_update_modifies_shortcode_successfully(): void
    {
        $shortcode = Shortcode::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', $shortcode), [
                'key' => $shortcode->key,
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ])
            ->assertSessionHasNoErrors();

        $shortcode->refresh();
        $this->assertEquals('Updated Name', $shortcode->name);
        $this->assertEquals('Updated description', $shortcode->description);
    }

    public function test_update_allows_same_key_for_the_shortcode(): void
    {
        $shortcode = Shortcode::factory()->create(['key' => 'my-shortcode']);

        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', $shortcode), [
                'key' => $shortcode->key,
                'name' => 'Updated Name',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shortcodes', [
            'id' => $shortcode->id,
            'key' => 'my-shortcode',
        ]);
    }

    public function test_update_validates_key_is_required(): void
    {
        $shortcode = Shortcode::factory()->create();

        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', $shortcode), [
                'name' => 'Updated Name',
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_update_validates_name_is_required(): void
    {
        $shortcode = Shortcode::factory()->create();

        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', $shortcode), [
                'key' => 'valid-key',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_update_validates_key_uniqueness_across_other_shortcodes(): void
    {
        $shortcode = Shortcode::factory()->create(['key' => 'key-one']);
        $other = Shortcode::factory()->create(['key' => 'key-two']);

        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', $shortcode), [
                'key' => $other->key,
                'name' => 'Test',
            ])
            ->assertSessionHasErrors(['key']);
    }

    public function test_update_returns_404_for_nonexistent_shortcode(): void
    {
        $this->actingAs($this->user)
            ->put(route('settings.shortcodes.update', 99999999), [
                'key' => 'test',
                'name' => 'Test',
            ])
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // DELETE /settings/shortcodes/{shortcode} (destroy)
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_shortcode(): void
    {
        $shortcode = Shortcode::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('settings.shortcodes.destroy', $shortcode))
            ->assertRedirect(route('settings.shortcodes.index'));

        $this->assertDatabaseMissing('shortcodes', ['id' => $shortcode->id]);
    }

    public function test_destroy_returns_json_for_ajax_request(): void
    {
        $shortcode = Shortcode::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson(route('settings.shortcodes.destroy', $shortcode))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_destroy_returns_404_for_nonexistent_shortcode(): void
    {
        $this->actingAs($this->user)
            ->delete(route('settings.shortcodes.destroy', 99999999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // POST /settings/shortcodes/{shortcode}/toggle (AJAX)
    // -------------------------------------------------------------------------

    public function test_toggle_changes_is_active_status(): void
    {
        $shortcode = Shortcode::factory()->active()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.toggle', $shortcode))
            ->assertOk()
            ->assertJson(['is_active' => false]);

        $shortcode->refresh();
        $this->assertFalse($shortcode->is_active);
    }

    public function test_toggle_activates_inactive_shortcode(): void
    {
        $shortcode = Shortcode::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.toggle', $shortcode))
            ->assertOk()
            ->assertJson(['is_active' => true]);

        $shortcode->refresh();
        $this->assertTrue($shortcode->is_active);
    }

    public function test_toggle_returns_404_for_nonexistent_shortcode(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.toggle', 99999999))
            ->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // POST /settings/shortcodes/order (AJAX)
    // -------------------------------------------------------------------------

    public function test_update_order_changes_sort_order(): void
    {
        $s1 = Shortcode::factory()->create(['sort_order' => 0]);
        $s2 = Shortcode::factory()->create(['sort_order' => 1]);
        $s3 = Shortcode::factory()->create(['sort_order' => 2]);

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.order'), [
                'order' => [$s3->id, $s1->id, $s2->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $s1->refresh();
        $s2->refresh();
        $s3->refresh();

        $this->assertEquals(1, $s1->sort_order);
        $this->assertEquals(2, $s2->sort_order);
        $this->assertEquals(0, $s3->sort_order);
    }

    public function test_update_order_validates_order_is_required(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.order'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    public function test_update_order_validates_order_must_be_array(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.order'), [
                'order' => 'not-an-array',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    public function test_update_order_validates_ids_must_exist(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.order'), [
                'order' => [99999999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order.0']);
    }

    // -------------------------------------------------------------------------
    // POST /settings/shortcodes/bulk-action (AJAX)
    // -------------------------------------------------------------------------

    public function test_bulk_action_activate_multiple_shortcodes(): void
    {
        $s1 = Shortcode::factory()->inactive()->create();
        $s2 = Shortcode::factory()->inactive()->create();
        $s3 = Shortcode::factory()->inactive()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'action' => 'activate',
                'ids' => [$s1->id, $s2->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $s1->refresh();
        $s2->refresh();
        $s3->refresh();

        $this->assertTrue($s1->is_active);
        $this->assertTrue($s2->is_active);
        $this->assertFalse($s3->is_active);
    }

    public function test_bulk_action_deactivate_multiple_shortcodes(): void
    {
        $s1 = Shortcode::factory()->active()->create();
        $s2 = Shortcode::factory()->active()->create();
        $s3 = Shortcode::factory()->active()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'action' => 'deactivate',
                'ids' => [$s1->id, $s2->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $s1->refresh();
        $s2->refresh();
        $s3->refresh();

        $this->assertFalse($s1->is_active);
        $this->assertFalse($s2->is_active);
        $this->assertTrue($s3->is_active);
    }

    public function test_bulk_action_delete_multiple_shortcodes(): void
    {
        $s1 = Shortcode::factory()->create();
        $s2 = Shortcode::factory()->create();
        $s3 = Shortcode::factory()->create();

        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'action' => 'delete',
                'ids' => [$s1->id, $s2->id],
            ])
            ->assertOk()
            ->assertJson(['count' => 2]);

        $this->assertDatabaseMissing('shortcodes', ['id' => $s1->id]);
        $this->assertDatabaseMissing('shortcodes', ['id' => $s2->id]);
        $this->assertDatabaseHas('shortcodes', ['id' => $s3->id]);
    }

    public function test_bulk_action_validates_action_is_required(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'ids' => [1, 2],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_bulk_action_validates_action_must_be_valid(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'action' => 'invalid-action',
                'ids' => [1, 2],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_bulk_action_validates_ids_are_required(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'action' => 'activate',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    public function test_bulk_action_validates_ids_must_exist(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('settings.shortcodes.bulk-action'), [
                'action' => 'activate',
                'ids' => [99999999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0']);
    }

    // -------------------------------------------------------------------------
    // GET /api/page-shortcodes
    // -------------------------------------------------------------------------

    public function test_api_index_returns_only_active_shortcodes(): void
    {
        Shortcode::factory()->count(2)->active()->create();
        Shortcode::factory()->count(3)->inactive()->create();

        $response = $this->actingAs($this->user)
            ->getJson(route('api.page-shortcodes'))
            ->assertOk()
            ->json();

        $this->assertCount(2, $response);
    }

    public function test_api_index_returns_required_fields(): void
    {
        Shortcode::factory()->active()->create([
            'key' => 'test-key',
            'name' => 'Test Name',
            'description' => 'Test description',
            'icon' => 'fas fa-star',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('api.page-shortcodes'))
            ->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'key', 'name', 'description', 'icon', 'config_fields', 'shortcode_template'],
            ]);
    }

    public function test_api_index_requires_authentication(): void
    {
        Gate::before(fn () => null);

        Shortcode::factory()->active()->create();

        $this->getJson(route('api.page-shortcodes'))
            ->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // GET /api/runtime-shortcodes
    // -------------------------------------------------------------------------

    public function test_api_runtime_index_returns_json(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('api.runtime-shortcodes'))
            ->assertOk();
    }

    public function test_api_runtime_index_requires_authentication(): void
    {
        Gate::before(fn () => null);

        $this->getJson(route('api.runtime-shortcodes'))
            ->assertUnauthorized();
    }
}
