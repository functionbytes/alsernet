<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Http\Requests\UploadFilesRequest;
use Modules\Attention\Models\Attention;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Controller for managing PQRSF file attachments
 * Handles upload, list, download, and delete operations
 */
class AttentionFileController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // All file operations require authentication
        $this->middleware('auth:sanctum');
    }

    /**
     * Upload multiple files to a PQRSF
     * POST /api/settings/pqrsf/{radicado}/files
     */
    public function upload(UploadFilesRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Validate that attention can receive files (not closed)
            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden agregar archivos a una solicitud cerrada',
                ], 422);
            }

            $uploadedFiles = [];
            $collection = $request->input('collection', 'attachments');

            // Upload each file
            foreach ($request->file('files') as $file) {
                $media = $attention->addMedia($file)
                    ->withCustomProperties([
                        'uploaded_by' => auth()->id(),
                        'description' => $request->input('description'),
                    ])
                    ->toMediaCollection($collection);

                $uploadedFiles[] = [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'original_name' => $media->name,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'mime_type' => $media->mime_type,
                    'extension' => $media->extension,
                    'collection' => $media->collection_name,
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at->toIso8601String(),
                ];
            }

            // Log the action
            $fileNames = collect($uploadedFiles)->pluck('name')->implode(', ');
            $attention->logAction(
                'files_uploaded',
                "Archivos subidos ({$collection}): {$fileNames}",
                auth()->id()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) === 1
                    ? 'Archivo subido exitosamente'
                    : count($uploadedFiles).' archivos subidos exitosamente',
                'data' => $uploadedFiles,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error uploading files', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir los archivos',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List all files for a PQRSF
     * GET /api/settings/pqrsf/{radicado}/files
     */
    public function list(Request $request, string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Optional collection filter
            $collection = $request->input('collection');

            // Get media from all collections or specific collection
            $mediaQuery = $collection
                ? $attention->getMedia($collection)
                : $attention->getMedia();

            $files = $mediaQuery->map(function (Media $media) {
                return [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'original_name' => $media->name,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'mime_type' => $media->mime_type,
                    'extension' => $media->extension,
                    'collection' => $media->collection_name,
                    'url' => $media->getUrl(),
                    'preview_url' => $media->hasGeneratedConversion('thumb')
                        ? $media->getUrl('thumb')
                        : null,
                    'custom_properties' => $media->custom_properties,
                    'uploaded_by' => $media->getCustomProperty('uploaded_by'),
                    'description' => $media->getCustomProperty('description'),
                    'created_at' => $media->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $files,
                'meta' => [
                    'total' => $files->count(),
                    'collection' => $collection,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error listing files', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }

    /**
     * Download a specific file
     * GET /api/settings/pqrsf/{radicado}/files/{mediaId}/download
     */
    public function download(string $radicado, int $mediaId): mixed
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Find the media belonging to this attention
            $media = $attention->getMedia()->firstWhere('id', $mediaId);

            if (! $media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado',
                ], 404);
            }

            // Log the download
            $attention->logAction(
                'file_downloaded',
                "Archivo descargado: {$media->file_name}",
                auth()->id()
            );

            // Return file download response
            return response()->download($media->getPath(), $media->file_name);

        } catch (Throwable $e) {
            Log::error('Error downloading file', [
                'radicado' => $radicado,
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete a file from a PQRSF
     * DELETE /api/settings/pqrsf/{radicado}/files/{mediaId}
     */
    public function delete(string $radicado, int $mediaId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Validate that attention can have files deleted (not closed)
            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden eliminar archivos de una solicitud cerrada',
                ], 422);
            }

            // Find the media belonging to this attention
            $media = $attention->getMedia()->firstWhere('id', $mediaId);

            if (! $media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado',
                ], 404);
            }

            // Store file info for logging before deletion
            $fileName = $media->file_name;
            $collection = $media->collection_name;

            // Delete the media file
            $media->delete();

            // Log the action
            $attention->logAction(
                'file_deleted',
                "Archivo eliminado ({$collection}): {$fileName}",
                auth()->id()
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente',
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error deleting file', [
                'radicado' => $radicado,
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get file information
     * GET /api/settings/pqrsf/{radicado}/files/{mediaId}
     */
    public function show(string $radicado, int $mediaId): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Find the media belonging to this attention
            $media = $attention->getMedia()->firstWhere('id', $mediaId);

            if (! $media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'original_name' => $media->name,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'mime_type' => $media->mime_type,
                    'extension' => $media->extension,
                    'collection' => $media->collection_name,
                    'url' => $media->getUrl(),
                    'path' => $media->getPath(),
                    'disk' => $media->disk,
                    'conversions' => $media->getGeneratedConversions()
                        ->map(fn ($generated, $name) => [
                            'name' => $name,
                            'url' => $generated ? $media->getUrl($name) : null,
                        ]),
                    'custom_properties' => $media->custom_properties,
                    'uploaded_by' => $media->getCustomProperty('uploaded_by'),
                    'description' => $media->getCustomProperty('description'),
                    'created_at' => $media->created_at->toIso8601String(),
                    'updated_at' => $media->updated_at->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error getting file info', [
                'radicado' => $radicado,
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Archivo no encontrado',
            ], 404);
        }
    }

    /**
     * Bulk delete files
     * POST /api/settings/pqrsf/{radicado}/files/bulk-delete
     */
    public function bulkDelete(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'media_ids' => 'required|array|min:1',
            'media_ids.*' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Validate that attention can have files deleted (not closed)
            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden eliminar archivos de una solicitud cerrada',
                ], 422);
            }

            $deletedFiles = [];
            $notFoundFiles = [];

            foreach ($request->media_ids as $mediaId) {
                $media = $attention->getMedia()->firstWhere('id', $mediaId);

                if ($media) {
                    $deletedFiles[] = $media->file_name;
                    $media->delete();
                } else {
                    $notFoundFiles[] = $mediaId;
                }
            }

            // Log the action
            if (! empty($deletedFiles)) {
                $fileNames = implode(', ', $deletedFiles);
                $attention->logAction(
                    'files_bulk_deleted',
                    "Archivos eliminados en lote: {$fileNames}",
                    auth()->id()
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($deletedFiles).' archivo(s) eliminado(s) exitosamente',
                'data' => [
                    'deleted' => count($deletedFiles),
                    'not_found' => count($notFoundFiles),
                    'deleted_files' => $deletedFiles,
                    'not_found_ids' => $notFoundFiles,
                ],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error bulk deleting files', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar los archivos',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get file statistics for a PQRSF
     * GET /api/settings/pqrsf/{radicado}/files/stats
     */
    public function stats(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            $allMedia = $attention->getMedia();

            // Group by collection
            $byCollection = $allMedia->groupBy('collection_name')->map(function ($collection) {
                return [
                    'count' => $collection->count(),
                    'total_size' => $collection->sum('size'),
                    'human_size' => $this->formatBytes($collection->sum('size')),
                ];
            });

            // Group by mime type
            $byMimeType = $allMedia->groupBy('mime_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_size' => $group->sum('size'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_files' => $allMedia->count(),
                    'total_size' => $allMedia->sum('size'),
                    'total_human_size' => $this->formatBytes($allMedia->sum('size')),
                    'by_collection' => $byCollection,
                    'by_mime_type' => $byMimeType,
                    'oldest_file' => $allMedia->sortBy('created_at')->first()?->created_at,
                    'newest_file' => $allMedia->sortByDesc('created_at')->first()?->created_at,
                ],
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
