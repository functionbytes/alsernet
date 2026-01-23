<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Media\Entities\MediaFile;
use Modules\Media\Entities\MediaFolder;

class MediaManagerController extends Controller
{
    /**
     * Obtener el disco de almacenamiento actualmente seleccionado
     */
    private function getActiveDisk(): string
    {
        return session('media_active_disk', 'media');
    }

    /**
     * Obtener lista de discos disponibles para el Media Manager
     */
    private function getAvailableDisks(): array
    {
        $disks = [];
        $configDisks = config('filesystems.disks');

        foreach ($configDisks as $diskName => $diskConfig) {
            $disks[$diskName] = [
                'name' => $diskName,
                'driver' => $diskConfig['driver'] ?? 'unknown',
                'label' => ucfirst($diskName),
            ];
        }

        return $disks;
    }

    /**
     * Cambiar el disco de almacenamiento activo
     */
    public function setActiveDisk(Request $request): JsonResponse
    {
        $request->validate([
            'disk' => 'required|string',
        ]);

        $disk = $request->string('disk');

        // Verificar que el disco exista en la configuración
        if (! config("filesystems.disks.{$disk}")) {
            return response()->json([
                'success' => false,
                'message' => 'El disco seleccionado no existe',
            ], 404);
        }

        session(['media_active_disk' => $disk]);

        return response()->json([
            'success' => true,
            'message' => "Disco cambiado a: {$disk}",
            'disk' => $disk,
        ]);
    }

    public function index(): View
    {
        return view('media::index', [
            'activeDisk' => $this->getActiveDisk(),
            'availableDisks' => $this->getAvailableDisks(),
        ]);
    }

    public function getList(Request $request): JsonResponse
    {
        $folderId = $request->integer('folder_id', 0);
        $search = $request->string('search', '');
        $view = $request->string('view', 'all');
        $perPage = $request->integer('per_page', 30);
        $page = $request->integer('page', 1);
        $activeDisk = $this->getActiveDisk();

        // Filtrar por disco activo, incluyendo archivos sin disco asignado cuando el disco es 'media'
        $query = MediaFile::query()->byUser()->where(function ($q) use ($activeDisk) {
            $q->where('disk', $activeDisk);
            // Si el disco activo es 'media', incluir también archivos sin disco asignado (legacy)
            if ($activeDisk === 'media') {
                $q->orWhereNull('disk');
            }
        });

        // Filtrar carpetas por disco activo
        $folderQuery = MediaFolder::query()->byUser()->where(function ($q) use ($activeDisk) {
            $q->where('disk', $activeDisk);
            // Si el disco activo es 'media', incluir también carpetas sin disco asignado (legacy)
            if ($activeDisk === 'media') {
                $q->orWhereNull('disk');
            }
        });

        // Breadcrumbs
        $breadcrumbs = [];
        if ($folderId > 0) {
            $currentFolder = MediaFolder::find($folderId);
            if ($currentFolder) {
                $breadcrumbs = $this->getBreadcrumbs($currentFolder);
            }
        } else {
            $breadcrumbs = [[
                'id' => 0,
                'name' => 'Todos los archivos',
                'icon' => 'fa-duotone fas fa-folder-open',
            ]];
        }

        // Apply filters
        if ($search) {
            $query->where('name', 'LIKE', '%'.$search.'%');
            $folderQuery->where('name', 'LIKE', '%'.$search.'%');
        }

        // Filter by view type
        match ($view) {
            'trash' => [
                $query->onlyTrashed(),
                $folderQuery->onlyTrashed(),
            ],
            'recent' => [
                $query->where('created_at', '>=', now()->subHours(24))
                    ->orderByDesc('created_at'),
                $folderQuery->whereRaw('1 = 0'), // No folders in recent view
            ],
            'favorites' => [
                $query->where('is_favorite', true),
                $folderQuery->whereRaw('1 = 0'), // No folders in favorites view
            ],
            default => [
                // Normal view - apply folder navigation
                $folderId > 0
                    ? $query->where('folder_id', $folderId)
                    : $query->whereNull('folder_id'),
                $folderId > 0
                    ? $folderQuery->where('parent_id', $folderId)
                    : $folderQuery->root(),
            ],
        };

        // Get folders and files
        $folders = $folderQuery
            ->orderBy('name')
            ->get();

        $files = $query
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'folders' => $folders->map(fn (MediaFolder $folder) => [
                'id' => $folder->id,
                'uid' => $folder->uid,
                'name' => $folder->name,
                'icon' => 'fa-duotone fas fa-folder',
                'color' => $folder->color,
                'created_at' => $folder->created_at->format('Y-m-d H:i'),
                'children_count' => $folder->children()->count(),
                'files_count' => $folder->files()->count(),
            ]),
            'files' => $files->map(fn (MediaFile $file) => [
                'id' => $file->id,
                'uid' => $file->uid,
                'name' => $file->name,
                'type' => $file->type,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'human_size' => $file->human_size,
                'url' => $file->url,
                'alt' => $file->alt,
                'created_at' => $file->created_at->format('Y-m-d H:i'),
                'is_trash' => $file->trashed(),
            ]),
            'breadcrumbs' => $breadcrumbs,
            'pagination' => [
                'current_page' => $files->currentPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
                'last_page' => $files->lastPage(),
            ],
        ]);
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $file = $request->file('file');
        $folderId = $request->integer('folder_id');
        $activeDisk = $this->getActiveDisk();

        // Guardar archivo
        $path = "{$folderId}";
        $storedPath = $file->store($path, $activeDisk);

        // Crear registro en BD
        $mediaFile = MediaFile::create([
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'url' => $storedPath,
            'folder_id' => $folderId > 0 ? $folderId : null,
            'user_id' => auth()->id(),
            'disk' => $activeDisk,
            'metadata' => $this->extractMetadata($file),
        ]);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $mediaFile->id,
                'uid' => $mediaFile->uid,
                'name' => $mediaFile->name,
                'type' => $mediaFile->type,
                'url' => $mediaFile->url,
                'size' => $mediaFile->human_size,
            ],
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        $activeDisk = $this->getActiveDisk();

        $folder = MediaFolder::create([
            'name' => $request->string('name'),
            'parent_id' => $request->integer('parent_id', 0) > 0 ? $request->integer('parent_id') : null,
            'user_id' => auth()->id(),
            'disk' => $activeDisk,
        ]);

        return response()->json([
            'success' => true,
            'folder' => [
                'id' => $folder->id,
                'uid' => $folder->uid,
                'name' => $folder->name,
                'color' => $folder->color,
            ],
        ]);
    }

    public function renameFile(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $file->update(['name' => $request->string('name')]);

        return response()->json(['success' => true]);
    }

    public function renameFolder(Request $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $folder->update(['name' => $request->string('name')]);

        return response()->json(['success' => true]);
    }

    public function deleteFile(MediaFile $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $file->delete();

        return response()->json(['success' => true, 'message' => 'Archivo eliminado']);
    }

    public function copyFile(MediaFile $file): JsonResponse
    {
        $this->authorize('create', MediaFile::class);

        try {
            $activeDisk = $file->disk ?? 'media';

            // Get original file info, removing disk prefix if it exists
            $originalPath = preg_replace('/^(media|public|local)\//', '', $file->url);

            // Check if source file exists
            if (! Storage::disk($activeDisk)->exists($originalPath)) {
                return response()->json(['message' => 'El archivo original no existe'], 404);
            }

            // Generate new filename with "_copia" suffix
            $fileName = pathinfo($file->name, PATHINFO_FILENAME);
            $fileExtension = pathinfo($file->name, PATHINFO_EXTENSION);
            $newFileName = $fileName.'_copia.'.$fileExtension;

            // Get the directory from the original path
            $directory = dirname($originalPath);

            // Generate a unique filename (similar to Laravel's store())
            $newStoredPath = $directory.'/'.pathinfo($newFileName, PATHINFO_FILENAME).'_'.uniqid().'.'.pathinfo($newFileName, PATHINFO_EXTENSION);

            // Copy the file in storage
            Storage::disk($activeDisk)->copy($originalPath, $newStoredPath);

            // Create new database record for the copied file
            $newFile = MediaFile::create([
                'name' => $newFileName,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'url' => $newStoredPath,
                'folder_id' => $file->folder_id,
                'user_id' => auth()->id(),
                'disk' => $activeDisk,
                'metadata' => $file->metadata ?? [],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo copiado exitosamente',
                'file' => [
                    'id' => $newFile->id,
                    'uid' => $newFile->uid,
                    'name' => $newFile->name,
                    'type' => $newFile->type,
                    'url' => $newFile->url,
                    'size' => $newFile->human_size,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al copiar el archivo: '.$e->getMessage()], 500);
        }
    }

    public function deleteFolder(MediaFolder $folder): JsonResponse
    {
        $this->authorize('delete', $folder);

        $folder->delete();

        return response()->json(['success' => true, 'message' => 'Carpeta eliminada']);
    }

    public function restoreFile(MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $file->restore();

        return response()->json(['success' => true]);
    }

    public function restoreFolder(MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $folder->restore();

        return response()->json(['success' => true]);
    }

    public function moveFile(Request $request, MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $request->validate([
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $file->update(['folder_id' => $request->integer('folder_id', 0) > 0 ? $request->integer('folder_id') : null]);

        return response()->json(['success' => true]);
    }

    public function moveFolder(Request $request, MediaFolder $folder): JsonResponse
    {
        $this->authorize('update', $folder);

        $request->validate([
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        $folder->update(['parent_id' => $request->integer('parent_id', 0) > 0 ? $request->integer('parent_id') : null]);

        return response()->json(['success' => true]);
    }

    private function getBreadcrumbs(MediaFolder $folder): array
    {
        $breadcrumbs = [];
        $current = $folder;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'icon' => 'fa-duotone fas fa-folder',
            ]);
            $current = $current->parent_id ? $current->parent : null;
        }

        return $breadcrumbs;
    }

    private function extractMetadata(UploadedFile $file): array
    {
        $metadata = [];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions) {
                $metadata['width'] = $dimensions[0];
                $metadata['height'] = $dimensions[1];
            }
        }

        return $metadata;
    }

    public function uploadFromUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|max:2048',
            'filename' => 'nullable|string|max:255',
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        try {
            $url = $request->string('url');
            $customFilename = $request->string('filename', '');
            $folderId = $request->integer('folder_id', 0);
            $activeDisk = $this->getActiveDisk();

            // Download file from URL
            $response = @file_get_contents($url);
            if ($response === false) {
                return response()->json(['message' => 'No se pudo descargar el archivo desde la URL'], 400);
            }

            // Get filename from URL or use custom
            $filename = $customFilename ?: basename(parse_url($url, PHP_URL_PATH)) ?: 'descargado_'.time();
            if (! str_contains($filename, '.')) {
                $filename .= '.bin';
            }

            // Create temp file
            $tempPath = storage_path('temp/'.bin2hex(random_bytes(16)));
            @mkdir(dirname($tempPath), 0755, true);
            file_put_contents($tempPath, $response);

            // Get file size
            $size = filesize($tempPath);
            if ($size > 104857600) { // 100MB
                @unlink($tempPath);

                return response()->json(['message' => 'El archivo excede el límite de 100MB'], 413);
            }

            // Get MIME type
            $mimeType = mime_content_type($tempPath) ?: 'application/octet-stream';

            // Determine file type
            $type = $this->getFileTypeFromMime($mimeType);

            // Store file
            $path = "{$folderId}";
            $storedPath = Storage::disk($activeDisk)->putFileAs($path, new \Symfony\Component\HttpFoundation\File\File($tempPath), $filename);

            // Create database record
            $mediaFile = MediaFile::create([
                'name' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
                'url' => $storedPath,
                'folder_id' => $folderId > 0 ? $folderId : null,
                'user_id' => auth()->id(),
                'disk' => $activeDisk,
                'metadata' => [],
            ]);

            // Clean up temp file
            @unlink($tempPath);

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $mediaFile->id,
                    'uid' => $mediaFile->uid,
                    'name' => $mediaFile->name,
                    'type' => $mediaFile->type,
                    'url' => $mediaFile->url,
                    'size' => $mediaFile->human_size,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    private function getFileTypeFromMime(string $mimeType): string
    {
        $types = [
            'image' => ['image/'],
            'video' => ['video/'],
            'audio' => ['audio/'],
            'pdf' => ['application/pdf'],
            'document' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'],
            'spreadsheet' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'archive' => ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'],
            'code' => ['text/x-c', 'text/x-php', 'text/x-python', 'application/json'],
        ];

        foreach ($types as $type => $mimes) {
            foreach ($mimes as $mime) {
                if (str_starts_with($mimeType, $mime)) {
                    return $type;
                }
            }
        }

        return 'file';
    }

    public function toggleFavorite(MediaFile $file): JsonResponse
    {
        $this->authorize('update', $file);

        $file->update(['is_favorite' => ! $file->is_favorite]);

        return response()->json([
            'success' => true,
            'is_favorite' => $file->is_favorite,
            'message' => $file->is_favorite ? 'Archivo agregado a favoritos' : 'Archivo eliminado de favoritos',
        ]);
    }

    public function emptyTrash(): JsonResponse
    {
        try {
            // Delete all trashed files
            $trashedFiles = MediaFile::byUser()->onlyTrashed()->get();
            foreach ($trashedFiles as $file) {
                $fileDisk = $file->disk ?? 'media';
                // Delete physical file
                $path = preg_replace('/^(media|public|local)\//', '', $file->url);
                if (Storage::disk($fileDisk)->exists($path)) {
                    Storage::disk($fileDisk)->delete($path);
                }
                // Force delete from database
                $file->forceDelete();
            }

            // Force delete all trashed folders
            MediaFolder::byUser()->onlyTrashed()->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Papelera vaciada exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vaciar la papelera: '.$e->getMessage(),
            ], 500);
        }
    }
}
