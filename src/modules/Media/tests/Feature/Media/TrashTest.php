<?php

namespace Modules\Media\Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_delete_soft_deletes_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.delete']);

        $file = MediaFile::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson(route('media.files.delete', $file))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('media_files', ['id' => $file->id]);
    }

    public function test_restore_file_from_trash(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $file = MediaFile::factory()->for($user)->create(['deleted_at' => now()]);

        $this->actingAs($user)
            ->postJson(route('media.files.restore', $file->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_files', [
            'id' => $file->id,
            'deleted_at' => null,
        ]);
    }

    public function test_empty_trash_force_deletes_user_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.force-delete']);

        $file = MediaFile::factory()->for($user)->create(['deleted_at' => now()]);

        $this->actingAs($user)
            ->deleteJson(route('media.trash.empty'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('media_files', ['id' => $file->id]);
    }

    public function test_deleting_folder_cascades_soft_delete_to_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.delete']);

        $folder = MediaFolder::factory()->for($user)->create();
        $file = MediaFile::factory()->for($user)->create(['folder_id' => $folder->id]);

        $this->actingAs($user)
            ->deleteJson(route('media.folders.delete', $folder))
            ->assertOk();

        $this->assertSoftDeleted('media_folders', ['id' => $folder->id]);
        $this->assertSoftDeleted('media_files', ['id' => $file->id]);
    }

    public function test_restoring_folder_cascades_restore_to_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $folder = MediaFolder::factory()->for($user)->create(['deleted_at' => now()]);
        $file = MediaFile::factory()->for($user)->create([
            'folder_id' => $folder->id,
            'deleted_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('media.folders.restore', $folder->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_folders', ['id' => $folder->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('media_files', ['id' => $file->id, 'deleted_at' => null]);
    }

    public function test_non_owner_cannot_restore_file(): void
    {
        $owner = User::factory()->create();
        $other = $this->createUserWithMediaPermissions(['media.update']);

        $file = MediaFile::factory()->for($owner)->create(['deleted_at' => now()]);

        $this->actingAs($other)
            ->postJson(route('media.files.restore', $file->id))
            ->assertForbidden();
    }
}
