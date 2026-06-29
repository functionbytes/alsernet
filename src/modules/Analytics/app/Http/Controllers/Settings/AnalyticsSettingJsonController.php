<?php

namespace Modules\Analytics\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Analytics\Rules\AnalyticsCredentialRule;

class AnalyticsSettingJsonController extends Controller
{
    /** Fields that must never be included in any response payload. */
    private const SENSITIVE_FIELDS = ['private_key', 'private_key_id'];

    /** Required fields for a Google Service Account JSON credential. */
    private const REQUIRED_FIELDS = [
        'type',
        'project_id',
        'private_key_id',
        'private_key',
        'client_email',
        'client_id',
        'auth_uri',
        'token_uri',
    ];

    /**
     * Upload and validate JSON credentials file.
     */
    public function upload(Request $request): JsonResponse
    {
        $this->authorize('analytics.settings.update');

        $request->validate([
            'credentials_file' => [
                'required',
                'file',
                'mimes:json',
                'max:50', // 50 KB — a real service account JSON is ~2-3 KB
            ],
        ]);

        try {
            $file = $request->file('credentials_file');
            $content = file_get_contents($file->getRealPath());

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('analytics::analytics.validation.json_invalid', ['error' => json_last_error_msg()]));
            }

            if (! is_array($decoded)) {
                throw new \Exception(__('analytics::analytics.validation.json_not_object'));
            }

            $missing = array_filter(
                self::REQUIRED_FIELDS,
                fn (string $field) => empty($decoded[$field])
            );

            if (! empty($missing)) {
                throw new \Exception(__('analytics::analytics.validation.missing_fields', ['fields' => implode(', ', array_values($missing))]));
            }

            // Run full structural validation (type, email format, private key, URIs)
            $rule = new AnalyticsCredentialRule;
            $errors = [];

            $rule->validate('credentials', $content, function (string $message) use (&$errors): void {
                $errors[] = $message;
            });

            if (! empty($errors)) {
                throw new \Exception(implode(' ', $errors));
            }

            return response()->json([
                'status' => true,
                'message' => __('analytics::analytics.success.credentials_loaded'),
                'data' => [
                    'credentials' => $content,
                    'project_id' => $decoded['project_id'],
                    'client_email' => $decoded['client_email'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.errors.json_upload_failed', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Validate JSON credentials from textarea.
     */
    public function validateJson(Request $request): JsonResponse
    {
        $this->authorize('analytics.settings.update');

        $credentials = $request->input('credentials');

        if (empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.validation.credentials_required'),
            ], 422);
        }

        try {
            // Validate using the rule
            $rule = new AnalyticsCredentialRule;
            $errors = [];

            $rule->validate('credentials', $credentials, function ($message) use (&$errors) {
                $errors[] = $message;
            });

            if (! empty($errors)) {
                throw new \Exception(implode(' ', $errors));
            }

            $decoded = json_decode($credentials, true);

            return response()->json([
                'status' => true,
                'message' => __('analytics::analytics.success.credentials_validated_json'),
                'data' => [
                    'project_id' => $decoded['project_id'] ?? null,
                    'client_email' => $decoded['client_email'] ?? null,
                    'type' => $decoded['type'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download sample credentials template.
     */
    public function downloadTemplate(): JsonResponse
    {
        $this->authorize('analytics.settings.update');

        $template = [
            'type' => 'service_account',
            'project_id' => 'your-project-id',
            'private_key_id' => 'your-private-key-id',
            'private_key' => '-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY\n-----END PRIVATE KEY-----\n',
            'client_email' => 'your-service-account@your-project-id.iam.gserviceaccount.com',
            'client_id' => 'your-client-id',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/your-service-account%40your-project-id.iam.gserviceaccount.com',
        ];

        $filename = 'google-analytics-credentials-template.json';
        $content = json_encode($template, JSON_PRETTY_PRINT);

        return response()->json([
            'status' => true,
            'data' => [
                'filename' => $filename,
                'content' => $content,
            ],
        ]);
    }

    /**
     * Format and beautify JSON credentials.
     */
    public function formatJson(Request $request): JsonResponse
    {
        $this->authorize('analytics.settings.update');

        $credentials = $request->input('credentials');

        if (empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.validation.credentials_required'),
            ], 422);
        }

        try {
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('analytics::analytics.validation.json_invalid', ['error' => json_last_error_msg()]));
            }

            $safe = array_diff_key($decoded, array_flip(self::SENSITIVE_FIELDS));
            $formatted = json_encode($safe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return response()->json([
                'status' => true,
                'message' => __('analytics::analytics.success.json_formatted'),
                'data' => [
                    'formatted' => $formatted,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.errors.json_format_failed', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Extract project info from credentials.
     */
    public function extractInfo(Request $request): JsonResponse
    {
        $this->authorize('analytics.settings.update');

        $credentials = $request->input('credentials');

        if (empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.validation.credentials_required'),
            ], 422);
        }

        try {
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(__('analytics::analytics.validation.json_invalid_short'));
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'type' => $decoded['type'] ?? null,
                    'project_id' => $decoded['project_id'] ?? null,
                    'client_email' => $decoded['client_email'] ?? null,
                    'client_id' => $decoded['client_id'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => __('analytics::analytics.errors.json_extract_failed', ['error' => $e->getMessage()]),
            ], 422);
        }
    }
}
