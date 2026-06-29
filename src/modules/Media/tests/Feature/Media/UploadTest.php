<?php

namespace Modules\Media\Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Jobs\StripExifJob;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
        Queue::fake();
    }

    public function test_user_with_permission_can_upload_image(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $file]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('media_files', ['name' => 'test.jpg']);
    }

    public function test_upload_dispatches_strip_exif_job_for_images(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $file = UploadedFile::fake()->image('photo.jpg');

        $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $file])
            ->assertOk();

        Queue::assertPushed(StripExifJob::class);
    }

    public function test_user_without_permission_cannot_upload(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_upload_deduplication_returns_duplicate_flag(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $file = UploadedFile::fake()->image('duplicate.jpg');

        // First upload — creates the file
        $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('duplicate', false);

        // Re-create the same fake file with identical content to produce same hash
        $sameFile = UploadedFile::fake()->createWithContent('duplicate.jpg', $file->get());

        $response = $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $sameFile]);

        $response->assertOk()
            ->assertJsonPath('duplicate', true);
    }

    public function test_upload_rejects_disallowed_mime(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $this->actingAs($user)
            ->postJson(route('media.files.upload'), ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_without_auth_redirects(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $this->post(route('media.files.upload'), ['file' => $file])
            ->assertRedirect();
    }

    public function test_upload_without_file_returns_validation_error(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create']);

        $this->actingAs($user)
            ->postJson(route('media.files.upload'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }
}
