<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Export endpoints — authentication required.
 */
class AttentionExportApiController extends Controller
{
    /**
     * Request export of peticiones data
     * POST /api/attentions/export
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:excel,pdf,csv',
            'filters' => 'nullable|array',
        ]);

        try {
            $token = bin2hex(random_bytes(32));

            cache()->put("export_{$token}", [
                'user_id' => auth()->id(),
                'format' => $request->format,
                'filters' => $request->filters ?? [],
                'status' => 'pending',
                'created_at' => now()->toIso8601String(),
            ], now()->addHour());

            // TODO: Dispatch job to generate export
            // dispatch(new GenerateAttentionExportJob($token, $request->format, $request->filters ?? []));

            Log::info('Export requested', [
                'user_id' => auth()->id(),
                'format' => $request->format,
                'token' => $token,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exportación solicitada. Recibirá una notificación cuando esté lista.',
                'data' => [
                    'token' => $token,
                    'download_url' => route('api.attentions.export.download', ['token' => $token]),
                    'expires_at' => now()->addHour()->toIso8601String(),
                ],
            ], 202);

        } catch (Throwable $e) {
            Log::error('Error requesting export', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar la exportación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download exported file
     * GET /api/attentions/export/{token}
     */
    public function downloadExport(string $token): JsonResponse|StreamedResponse
    {
        try {
            $exportData = cache()->get("export_{$token}");

            if (! $exportData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de exportación no válido o expirado',
                ], 404);
            }

            if ($exportData['user_id'] !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para descargar esta exportación',
                ], 403);
            }

            if ($exportData['status'] !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'La exportación aún está en proceso',
                    'data' => ['status' => $exportData['status']],
                ], 202);
            }

            $filePath = storage_path("app/exports/{$token}.{$exportData['format']}");

            if (! file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo de exportación no encontrado',
                ], 404);
            }

            $contentType = match ($exportData['format']) {
                'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf' => 'application/pdf',
                'csv' => 'text/csv',
                default => 'application/octet-stream',
            };

            $fileName = 'peticiones_export_'.now()->format('Y-m-d_His').".{$exportData['format']}";

            Log::info('Export downloaded', [
                'user_id' => auth()->id(),
                'token' => $token,
            ]);

            return response()->stream(
                function () use ($filePath) {
                    $stream = fopen($filePath, 'r');
                    fpassthru($stream);
                    fclose($stream);
                },
                200,
                [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                    'Content-Length' => filesize($filePath),
                ]
            );

        } catch (Throwable $e) {
            Log::error('Error downloading export', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar la exportación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
