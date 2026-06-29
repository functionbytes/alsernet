<?php

namespace Modules\Media\Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaFile;
use Modules\Media\Tests\MediaTestHelpers;
use Tests\TestCase;

class PathTraversalTest extends TestCase
{
    use MediaTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
    }

    public function test_copy_rejects_path_traversal_url(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create', 'media.update']);

        $file = MediaFile::factory()->for($user)->create([
            'url' => 'malicious/../../etc/passwd',
            'disk' => 'media',
        ]);

        $fileCountBefore = MediaFile::count();

        $response = $this->actingAs($user)
            ->postJson(route('media.files.copy', $file));

        // Must not succeed — 422, 404, or 500 but never 200 with a new file created
        $this->assertNotEquals(200, $response->status());
        $this->assertEquals($fileCountBefore, MediaFile::count());
    }

    public function test_copy_rejects_forbidden_php_extension(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create', 'media.update']);

        // Store a real file on the fake disk so the existence check passes
        Storage::disk('media')->put('0/malware.php', '<?php echo "pwned"; ?>');

        $file = MediaFile::factory()->for($user)->create([
            'name' => 'malware.php',
            'url' => '0/malware.php',
            'mime_type' => 'application/x-httpd-php',
            'disk' => 'media',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('media.files.copy', $file));

        $response->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'no permitido'));

        // Confirm no copy was created
        $this->assertEquals(1, MediaFile::count());
    }

    public function test_copy_rejects_phar_extension(): void
    {
        $user = $this->createUserWithMediaPermissions(['media.create', 'media.update']);

        Storage::disk('media')->put('0/payload.phar', 'phar content');

        $file = MediaFile::factory()->for($user)->create([
            'name' => 'payload.phar',
            'url' => '0/payload.phar',
            'disk' => 'media',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('media.files.copy', $file));

        $response->assertStatus(422);
        $this->assertEquals(1, MediaFile::count());
    }
}
