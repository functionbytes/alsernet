<?php

namespace Modules\Analytics\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Analytics\Rules\AnalyticsCredentialRule;

class AnalyticsSettingJsonController extends Controller
{
    /**
     * Upload and validate JSON credentials file.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'credentials_file' => [
                'required',
                'file',
                'mimes:json',
                'max:10240', // 10MB max
            ],
        ]);

        try {
            $file = $request->file('credentials_file');
            $content = file_get_contents($file->getRealPath());

            // Validate JSON format
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Archivo JSON inválido: '.json_last_error_msg());
            }

            // Validate credentials structure using the rule
            $rule = new AnalyticsCredentialRule;
            $errors = [];

            $rule->validate('credentials', $content, function ($message) use (&$errors) {
                $errors[] = $message;
            });

            if (! empty($errors)) {
                throw new \Exception(implode(' ', $errors));
            }

            return response()->json([
                'status' => true,
                'message' => 'Archivo de credenciales cargado y validado correctamente',
                'data' => [
                    'credentials' => $content,
                    'project_id' => $decoded['project_id'] ?? null,
                    'client_email' => $decoded['client_email'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al procesar archivo: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate JSON credentials from textarea.
     */
    public function validateJson(Request $request): JsonResponse
    {
        $credentials = $request->input('credentials');

        if (empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Las credenciales no pueden estar vacías',
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
                'message' => 'Credenciales validadas correctamente',
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
        $credentials = $request->input('credentials');

        if (empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Las credenciales no pueden estar vacías',
            ], 422);
        }

        try {
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido: '.json_last_error_msg());
            }

            $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return response()->json([
                'status' => true,
                'message' => 'JSON formateado correctamente',
                'data' => [
                    'formatted' => $formatted,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al formatear JSON: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Extract project info from credentials.
     */
    public function extractInfo(Request $request): JsonResponse
    {
        $credentials = $request->input('credentials');

        if (empty($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Las credenciales no pueden estar vacías',
            ], 422);
        }

        try {
            $decoded = json_decode($credentials, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido');
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'type' => $decoded['type'] ?? null,
                    'project_id' => $decoded['project_id'] ?? null,
                    'client_email' => $decoded['client_email'] ?? null,
                    'client_id' => $decoded['client_id'] ?? null,
                    'has_private_key' => isset($decoded['private_key']),
                    'auth_uri' => $decoded['auth_uri'] ?? null,
                    'token_uri' => $decoded['token_uri'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al extraer información: '.$e->getMessage(),
            ], 422);
        }
    }
}
