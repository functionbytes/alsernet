<?php

namespace Modules\Media\Tests\Feature\Media\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class MediaFileApiTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_api_requires_sanctum_auth(): void
    {
        $this->getJson(route('api.media.files.index'))
            ->assertUnauthorized();
    }

    public function test_api_index_returns_paginated_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view']);
        Sanctum::actingAs($user);

        MediaFile::factory()->count(3)->for($user)->create();

        $this->getJson(route('api.media.files.index'))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [['id', 'uid', 'name', 'mimeType', 'type', 'humanSize', 'isFavorite', 'createdAt']],
                    'meta' => ['total', 'per_page'],
                ],
            ]);
    }

    public function test_api_returns_camelcase_keys(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view']);
        Sanctum::actingAs($user);

        MediaFile::factory()->for($user)->create();

        $response = $this->getJson(route('api.media.files.index'))
            ->assertOk();

        $firstItem = $response->json('data.data.0');

        $this->assertArrayHasKey('mimeType', $firstItem);
        $this->assertArrayHasKey('humanSize', $firstItem);
        $this->assertArrayHasKey('isFavorite', $firstItem);
        $this->assertArrayHasKey('createdAt', $firstItem);
        $this->assertArrayNotHasKey('mime_type', $firstItem);
        $this->assertArrayNotHasKey('human_size', $firstItem);
    }

    public function test_api_store_uploads_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('api-upload.jpg');

        $this->postJson(route('api.media.files.store'), ['file' => $file])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'mimeType']]);

        $this->assertDatabaseHas('media_files', ['name' => 'api-upload.jpg']);
    }

    public function test_api_update_renames_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);
        Sanctum::actingAs($user);

        $file = MediaFile::factory()->for($user)->create(['name' => 'old-name.jpg']);

        $this->putJson(route('api.media.files.update', $file), ['name' => 'new-name.jpg'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'new-name.jpg');

        $this->assertDatabaseHas('media_files', ['id' => $file->id, 'name' => 'new-name.jpg']);
    }

    public function test_api_destroy_soft_deletes_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.delete']);
        Sanctum::actingAs($user);

        $file = MediaFile::factory()->for($user)->create();

        $this->deleteJson(route('api.media.files.destroy', $file))
            ->assertNoContent();

        $this->assertSoftDeleted('media_files', ['id' => $file->id]);
    }

    public function test_api_move_file_to_folder(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);
        Sanctum::actingAs($user);

        $file = MediaFile::factory()->for($user)->create();
        $folder = MediaFolder::factory()->for($user)->create();

        $this->postJson(route('api.media.files.move', $file), ['folder_id' => $folder->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_files', [
            'id' => $file->id,
            'folder_id' => $folder->id,
        ]);
    }

    public function test_api_returns_404_for_nonexistent_file(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view']);
        Sanctum::actingAs($user);

        $this->getJson(route('api.media.files.show', 999999))
            ->assertNotFound();
    }

    public function test_api_index_only_returns_authenticated_user_files(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view']);
        $other = $this->createUserWithMediaPermissions(['media.view']);

        MediaFile::factory()->count(2)->for($user)->create();
        MediaFile::factory()->count(3)->for($other)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.media.files.index'))
            ->assertOk();

        $this->assertEquals(2, $response->json('data.meta.total'));
    }
}
