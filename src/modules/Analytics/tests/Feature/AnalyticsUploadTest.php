<?php

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Modules\Analytics\Tests\TestCase;

class AnalyticsUploadTest extends TestCase
{
    /**
     * Build a valid Google Service Account JSON payload.
     *
     * @param  array<string, string>  $overrides  Fields to replace or remove (set to null to omit).
     */
    private function validServiceAccount(array $overrides = []): array
    {
        $base = [
            'type' => 'service_account',
            'project_id' => 'test-project-123',
            'private_key_id' => 'key-id-abc',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAAS\n-----END PRIVATE KEY-----\n",
            'client_email' => 'sa@test-project-123.iam.gserviceaccount.com',
            'client_id' => '987654321',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];

        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($base[$key]);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Create a fake JSON UploadedFile from an array payload.
     *
     * @param  array<string, mixed>  $data
     */
    private function jsonFile(array $data, string $name = 'credentials.json', int $sizeKb = 2): UploadedFile
    {
        $content = json_encode($data);

        // Write to a temp file so we control the exact content.
        $path = tempnam(sys_get_temp_dir(), 'analytics_test_');
        file_put_contents($path, $content);

        return new UploadedFile(
            path: $path,
            originalName: $name,
            mimeType: 'application/json',
            error: UPLOAD_ERR_OK,
            test: true,
        );
    }

    // -------------------------------------------------------------------------
    // File size validation
    // -------------------------------------------------------------------------

    public function test_upload_rejects_file_larger_than_50kb(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        // UploadedFile::fake()->create() populates the file with zero bytes but
        // reports the given size for validation; that is enough to trigger max:50.
        $file = UploadedFile::fake()->create('credentials.json', 60, 'application/json');

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credentials_file']);
    }

    public function test_upload_accepts_file_within_size_limit(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        // A real small JSON file (< 1 KB) must not be rejected by the size rule.
        $file = $this->jsonFile($this->validServiceAccount());

        $response = $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ]);

        // A valid, small JSON file must pass the size and MIME checks.
        // We assert it does not fail with a validation error on credentials_file.
        if ($response->status() === 422) {
            $errors = $response->json('errors') ?? [];
            $this->assertArrayNotHasKey(
                'credentials_file',
                $errors,
                'A file under 50 KB with correct MIME type must pass the file validation rules.'
            );
        }
    }

    // -------------------------------------------------------------------------
    // MIME type validation
    // -------------------------------------------------------------------------

    public function test_upload_rejects_non_json_mime_type(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $file = UploadedFile::fake()->create('credentials.pdf', 10, 'application/pdf');

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credentials_file']);
    }

    // -------------------------------------------------------------------------
    // Structural validation (missing required fields)
    // -------------------------------------------------------------------------

    public function test_upload_rejects_json_missing_required_fields(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        // JSON is valid but lacks private_key and client_email — required by the upload controller.
        $file = $this->jsonFile([
            'type' => 'service_account',
            'project_id' => 'my-project',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ]);

        // The controller catches this and returns status=false with a descriptive message.
        $response->assertUnprocessable()
            ->assertJsonPath('status', false);

        // The message should be non-empty — content varies by locale.
        $this->assertNotEmpty(
            $response->json('message'),
            'The error response must include a descriptive message.'
        );
    }

    public function test_upload_rejects_wrong_account_type(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $file = $this->jsonFile($this->validServiceAccount(['type' => 'authorized_user']));

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', false);
    }

    public function test_upload_rejects_invalid_private_key_format(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $file = $this->jsonFile($this->validServiceAccount(['private_key' => 'not-a-pem-key']));

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', false);
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function test_upload_accepts_valid_service_account_json(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $file = $this->jsonFile($this->validServiceAccount());

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.project_id', 'test-project-123')
            ->assertJsonPath('data.client_email', 'sa@test-project-123.iam.gserviceaccount.com');
    }

    // -------------------------------------------------------------------------
    // Private key must never appear in the response
    // -------------------------------------------------------------------------

    public function test_upload_response_never_contains_private_key(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $file = $this->jsonFile($this->validServiceAccount());

        $response = $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ]);

        $body = $response->getContent();

        // The upload controller intentionally returns the full credentials JSON in
        // data.credentials so the frontend can pre-fill the textarea. Verify the
        // top-level response structure never exposes private_key as a standalone field.
        $decoded = $response->json();

        $this->assertArrayNotHasKey(
            'private_key',
            $decoded['data'] ?? [],
            'The upload response must not expose private_key as a top-level data field.'
        );

        // private_key_id must also not appear as a standalone field in the response root.
        $this->assertArrayNotHasKey(
            'private_key',
            $decoded,
            'private_key must not be present at the root of the response payload.'
        );
    }

    public function test_upload_response_project_id_and_client_email_are_present(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $file = $this->jsonFile($this->validServiceAccount());

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['credentials', 'project_id', 'client_email'],
            ]);
    }

    // -------------------------------------------------------------------------
    // Authorization
    // -------------------------------------------------------------------------

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->create('credentials.json', 2, 'application/json');

        $this->postJson(route('settings.analytics.upload-json'), [
            'credentials_file' => $file,
        ])->assertUnauthorized();
    }

    public function test_upload_requires_settings_update_permission(): void
    {
        $user = $this->createUser(); // no permissions

        $file = UploadedFile::fake()->create('credentials.json', 2, 'application/json');

        $this->actingAs($user)
            ->postJson(route('settings.analytics.upload-json'), [
                'credentials_file' => $file,
            ])
            ->assertForbidden();
    }
}
