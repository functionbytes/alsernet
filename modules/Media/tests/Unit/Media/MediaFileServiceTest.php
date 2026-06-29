<?php

namespace Modules\Media\Tests\Unit\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Services\MediaFileService;
use Tests\TestCase;

class MediaFileServiceTest extends TestCase
{
    use RefreshDatabase;

    private MediaFileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
        Queue::fake();
        $this->service = app(MediaFileService::class);
    }

    public function test_upload_creates_media_file_with_ulid(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $result = $this->service->upload($file, null, 'media', $user->id);

        $this->assertFalse($result['duplicate']);
        $this->assertNotNull($result['file']->uid);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $result['file']->uid);
    }

    public function test_upload_detects_duplicate_by_hash(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('original.jpg');

        $first = $this->service->upload($file, null, 'media', $user->id);

        $sameFile = UploadedFile::fake()->createWithContent('original.jpg', $file->get());
        $second = $this->service->upload($sameFile, null, 'media', $user->id);

        $this->assertFalse($first['duplicate']);
        $this->assertTrue($second['duplicate']);
        $this->assertEquals($first['file']->id, $second['file']->id);
    }

    public function test_copy_creates_new_file_with_copia_suffix(): void
    {
        $user = User::factory()->create();

        Storage::disk('media')->put('0/photo.jpg', 'fake image content');

        $original = MediaFile::factory()->for($user)->create([
            'name' => 'photo.jpg',
            'url' => '0/photo.jpg',
            'disk' => 'media',
        ]);

        $copy = $this->service->copy($original, $user->id);

        $this->assertNotEquals($original->id, $copy->id);
        $this->assertStringContainsString('copia', $copy->name);
        $this->assertEquals($original->mime_type, $copy->mime_type);
        $this->assertEquals($original->folder_id, $copy->folder_id);
    }

    public function test_rename_updates_name(): void
    {
        $user = User::factory()->create();
        $file = MediaFile::factory()->for($user)->create(['name' => 'old.jpg']);

        $updated = $this->service->rename($file, 'new-name.jpg');

        $this->assertEquals('new-name.jpg', $updated->name);
        $this->assertDatabaseHas('media_files', ['id' => $file->id, 'name' => 'new-name.jpg']);
    }

    public function test_move_updates_folder_id(): void
    {
        $user = User::factory()->create();
        $folder = MediaFolder::factory()->for($user)->create();
        $file = MediaFile::factory()->for($user)->create(['folder_id' => null]);

        $updated = $this->service->move($file, $folder->id);

        $this->assertEquals($folder->id, $updated->folder_id);
        $this->assertDatabaseHas('media_files', ['id' => $file->id, 'folder_id' => $folder->id]);
    }

    public function test_move_to_null_removes_folder(): void
    {
        $user = User::factory()->create();
        $folder = MediaFolder::factory()->for($user)->create();
        $file = MediaFile::factory()->for($user)->create(['folder_id' => $folder->id]);

        $updated = $this->service->move($file, null);

        $this->assertNull($updated->folder_id);
    }

    public function test_toggle_favorite_creates_media_favorite(): void
    {
        $user = User::factory()->create();
        $file = MediaFile::factory()->for($user)->create();

        $isFavorite = $this->service->toggleFavorite($file, $user->id);

        $this->assertTrue($isFavorite);
        $this->assertDatabaseHas('media_favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ]);
    }

    public function test_toggle_favorite_removes_existing_favorite(): void
    {
        $user = User::factory()->create();
        $file = MediaFile::factory()->for($user)->create();

        MediaFavorite::create([
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ]);

        $isFavorite = $this->service->toggleFavorite($file, $user->id);

        $this->assertFalse($isFavorite);
        $this->assertDatabaseMissing('media_favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
        ]);
    }

    public function test_delete_soft_deletes_file(): void
    {
        $user = User::factory()->create();
        $file = MediaFile::factory()->for($user)->create();

        $this->service->delete($file);

        $this->assertSoftDeleted('media_files', ['id' => $file->id]);
    }

    public function test_restore_recovers_soft_deleted_file(): void
    {
        $user = User::factory()->create();
        $file = MediaFile::factory()->for($user)->create(['deleted_at' => now()]);

        $this->service->restore($file);

        $this->assertDatabaseHas('media_files', ['id' => $file->id, 'deleted_at' => null]);
    }
}
