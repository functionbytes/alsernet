<?php

namespace Modules\Media\Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_toggle_favorite_adds_to_media_favorites_table(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $file = MediaFile::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('media.files.toggle-favorite', $file))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_favorite', true);

        $this->assertDatabaseHas('media_favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ]);
    }

    public function test_toggle_favorite_removes_when_already_favorited(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);

        $file = MediaFile::factory()->for($user)->create();

        MediaFavorite::create([
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ]);

        $this->actingAs($user)
            ->postJson(route('media.files.toggle-favorite', $file))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_favorite', false);

        $this->assertDatabaseMissing('media_favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
        ]);
    }

    public function test_favorites_list_returns_user_favorites(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.view']);

        $file = MediaFile::factory()->for($user)->create();

        MediaFavorite::create([
            'user_id' => $user->id,
            'favoritable_id' => $file->id,
            'favoritable_type' => MediaFile::class,
        ]);

        $this->actingAs($user)
            ->getJson(route('media.list', ['view' => 'favorites']))
            ->assertOk()
            ->assertJsonStructure(['files', 'folders', 'breadcrumbs', 'pagination']);
    }

    public function test_toggle_favorite_is_scoped_to_authenticated_user(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.update']);
        $file = MediaFile::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('media.files.toggle-favorite', $file))
            ->assertOk();

        $this->assertEquals(1, MediaFavorite::where('user_id', $user->id)->count());
    }
}
