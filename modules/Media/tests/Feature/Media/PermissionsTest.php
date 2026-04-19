<?php

namespace Modules\Media\Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_view_any_requires_media_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.media.files.index'))
            ->assertForbidden();
    }

    public function test_authenticated_user_with_view_permission_can_list_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view']);

        $this->actingAs($user)
            ->getJson(route('api.media.files.index'))
            ->assertOk();
    }

    public function test_create_requires_media_create_permission(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_owner_can_delete_own_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.delete']);

        $file = MediaFile::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson(route('media.files.delete', $file))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_non_owner_cannot_delete_file(): void
    {
        $owner = User::factory()->create();
        $other = $this->createUserWithMediaPermissions(['media.delete']);

        $file = MediaFile::factory()->for($owner)->create();

        $this->actingAs($other)
            ->deleteJson(route('media.files.delete', $file))
            ->assertForbidden();
    }

    public function test_owner_can_update_own_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $file = MediaFile::factory()->for($user)->create();

        $this->actingAs($user)
            ->putJson(route('media.files.rename', $file), ['name' => 'nuevo-nombre.jpg'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_non_owner_cannot_update_file(): void
    {
        $owner = User::factory()->create();
        $other = $this->createUserWithMediaPermissions(['media.update']);

        $file = MediaFile::factory()->for($owner)->create();

        $this->actingAs($other)
            ->putJson(route('media.files.rename', $file), ['name' => 'hack.jpg'])
            ->assertForbidden();
    }

    public function test_guest_cannot_access_media_routes(): void
    {
        $this->getJson(route('api.media.files.index'))
            ->assertUnauthorized();
    }

    public function test_empty_trash_requires_force_delete_permission(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view', 'media.delete']);

        $this->actingAs($user)
            ->deleteJson(route('media.trash.empty'))
            ->assertForbidden();
    }
}
