<?php

namespace Modules\Media\Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class BulkActionTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_bulk_delete_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $files = MediaFile::factory()->count(3)->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'delete',
                'type' => 'file',
                'ids' => $files->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 3);

        foreach ($files as $file) {
            $this->assertSoftDeleted('media_files', ['id' => $file->id]);
        }
    }

    public function test_bulk_move_files_to_folder(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $files = MediaFile::factory()->count(2)->for($user)->create();
        $folder = MediaFolder::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'move',
                'type' => 'file',
                'ids' => $files->pluck('id')->toArray(),
                'folder_id' => $folder->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        foreach ($files as $file) {
            $this->assertDatabaseHas('media_files', [
                'id' => $file->id,
                'folder_id' => $folder->id,
            ]);
        }
    }

    public function test_bulk_favorite_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $files = MediaFile::factory()->count(2)->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'favorite',
                'type' => 'file',
                'ids' => $files->pluck('id')->toArray(),
            ])
            ->assertOk();

        foreach ($files as $file) {
            $this->assertDatabaseHas('media_favorites', [
                'user_id' => $user->id,
                'favoritable_id' => $file->id,
                'favoritable_type' => MediaFile::class,
            ]);
        }
    }

    public function test_bulk_unfavorite_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $files = MediaFile::factory()->count(2)->for($user)->create();

        // Create favorites first
        foreach ($files as $file) {
            MediaFavorite::create([
                'user_id' => $user->id,
                'favoritable_id' => $file->id,
                'favoritable_type' => MediaFile::class,
            ]);
        }

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'unfavorite',
                'type' => 'file',
                'ids' => $files->pluck('id')->toArray(),
            ])
            ->assertOk();

        foreach ($files as $file) {
            $this->assertDatabaseMissing('media_favorites', [
                'user_id' => $user->id,
                'favoritable_id' => $file->id,
            ]);
        }
    }

    public function test_bulk_only_affects_own_items(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);
        $otherUser = User::factory()->create();

        $ownFile = MediaFile::factory()->for($user)->create();
        $otherFile = MediaFile::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'delete',
                'type' => 'file',
                'ids' => [$ownFile->id, $otherFile->id],
            ])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->assertSoftDeleted('media_files', ['id' => $ownFile->id]);
        $this->assertDatabaseHas('media_files', ['id' => $otherFile->id, 'deleted_at' => null]);
    }

    public function test_bulk_action_requires_media_update_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'delete',
                'type' => 'file',
                'ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_bulk_action_rejects_invalid_action(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $this->actingAs($user)
            ->postJson(route('media.bulk-action'), [
                'action' => 'invalid_action',
                'type' => 'file',
                'ids' => [1],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    }
}
