<?php

namespace Modules\Reviews\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\Reviews\Jobs\ExportReviewsJob;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewExportService;
use Modules\Reviews\Tests\TestCase;

class ReviewExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_export_endpoint_dispatches_job_to_queue(): void
    {
        Queue::fake();

        $user = $this->createUser(['reviews.reviews.export']);

        $response = $this->actingAs($user)
            ->getJson(route('reviews.export'));

        $response->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(ExportReviewsJob::class, function (ExportReviewsJob $job) use ($user) {
            return $job->user->is($user);
        });
    }

    public function test_export_job_dispatched_to_exports_queue(): void
    {
        Queue::fake();

        $user = $this->createUser(['reviews.reviews.export']);

        $this->actingAs($user)
            ->getJson(route('reviews.export'));

        Queue::assertPushedOn('exports', ExportReviewsJob::class);
    }

    public function test_export_csv_file_is_created_with_correct_columns(): void
    {
        $user = $this->createUser(['reviews.reviews.export']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        Review::factory()->fiveStars()->for($location, 'location')->create([
            'reviewer_name' => 'Test Reviewer',
            'comment' => 'Great service!',
        ]);

        $service = app(ReviewExportService::class);
        $absolutePath = $service->exportToCsv([], $user->id);

        $this->assertFileExists($absolutePath);

        $contents = file_get_contents($absolutePath);

        // Verify all expected column headers are present
        $this->assertStringContainsString('ID', $contents);
        $this->assertStringContainsString('Location', $contents);
        $this->assertStringContainsString('Reviewer', $contents);
        $this->assertStringContainsString('Rating', $contents);
        $this->assertStringContainsString('Comment', $contents);
        $this->assertStringContainsString('Review Date', $contents);
        $this->assertStringContainsString('Has Reply', $contents);
    }

    public function test_export_csv_filename_contains_user_id(): void
    {
        $user = $this->createUser(['reviews.reviews.export']);

        $service = app(ReviewExportService::class);
        $absolutePath = $service->exportToCsv([], $user->id);

        $this->assertStringContainsString("_{$user->id}_", basename($absolutePath));
    }

    public function test_user_can_download_their_own_export_file(): void
    {
        $user = $this->createUser(['reviews.reviews.export']);

        // Create the file in the real (faked) local disk so the controller can find it
        $filename = "reviews_{$user->id}_2024-01-01_120000.csv";
        Storage::disk('local')->put("exports/{$filename}", "ID,Location\n1,Test\n");

        // Point the controller at the faked storage path
        $realPath = Storage::disk('local')->path("exports/{$filename}");

        // Bind the storage path so realpath() resolves it correctly
        // The controller uses storage_path('app/exports/') — we need to put the file there
        $storageExportsPath = storage_path('app/exports');

        if (! is_dir($storageExportsPath)) {
            mkdir($storageExportsPath, 0755, true);
        }

        file_put_contents($storageExportsPath.'/'.$filename, "ID,Location\n1,Test\n");

        $response = $this->actingAs($user)
            ->get(route('reviews.export.download', $filename));

        $response->assertOk();

        // Clean up
        @unlink($storageExportsPath.'/'.$filename);
    }

    public function test_user_cannot_download_another_users_export_file(): void
    {
        $userA = $this->createUser(['reviews.reviews.export']);
        $userB = $this->createUser(['reviews.reviews.export']);

        $filenameForB = "reviews_{$userB->id}_2024-01-01_120000.csv";

        $response = $this->actingAs($userA)
            ->get(route('reviews.export.download', $filenameForB));

        $response->assertForbidden();
    }

    public function test_export_endpoint_requires_export_permission(): void
    {
        $user = $this->createUser(); // no export permission

        $response = $this->actingAs($user)
            ->getJson(route('reviews.export'));

        $response->assertForbidden();
    }

    public function test_download_with_invalid_filename_characters_returns_error(): void
    {
        $user = $this->createUser(['reviews.reviews.export']);

        // A filename with a null byte or special chars triggers the regex check (400)
        // Slashes in the route segment cause a 404 at routing level — both are rejections
        $response = $this->actingAs($user)
            ->get('/reviews/export/download/..%2F..%2Fetc%2Fpasswd');

        // Either 400 (controller rejects) or 404 (routing rejects) is acceptable
        $this->assertTrue(
            in_array($response->status(), [400, 404], true),
            "Expected 400 or 404 but got {$response->status()}"
        );
    }

    public function test_export_job_passes_filters_to_service(): void
    {
        Queue::fake();

        $user = $this->createUser(['reviews.reviews.export']);
        $connection = $this->createConnection($user);
        $location = $this->createLocation($connection);

        $this->actingAs($user)->getJson(route('reviews.export', [
            'location_id' => $location->id,
            'date_from' => '2024-01-01',
        ]));

        Queue::assertPushed(ExportReviewsJob::class, function (ExportReviewsJob $job) use ($location) {
            return isset($job->filters['location_id'])
                && (string) $job->filters['location_id'] === (string) $location->id;
        });
    }
}
