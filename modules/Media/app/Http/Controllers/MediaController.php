<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Repositories\Interfaces\MediaFileInterface;
use Modules\Media\Repositories\Interfaces\MediaFolderInterface;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;

class MediaController extends Controller
{
    public function __construct(
        protected MediaFileInterface $fileRepository,
        protected MediaFolderInterface $folderRepository,
        protected MediaSettingInterface $settingRepository
    ) {}

    public function index(): View
    {
        return view('media::index', [
            'activeDisk' => $this->getActiveDisk(),
            'availableDisks' => $this->getAvailableDisks(),
        ]);
    }

    public function getList(Request $request): JsonResponse
    {
        $folderId = $request->integer('folder_id', 0) ?: null;
        $search = $request->string('search', '');
        $view = $request->string('view', 'all_media');
        $perPage = $request->integer('per_page', 30);
        $page = $request->integer('page', 1);

        $breadcrumbs = [];
        $files = [];
        $folders = [];

        switch ($view) {
            case 'trash':
                $breadcrumbs = [['id' => 0, 'name' => 'Papelera', 'icon' => 'fas fa-trash']];
                $items = $this->fileRepository->getTrashed($folderId);
                $folders = $items->where('is_folder', true)->values();
                $files = $items->where('is_folder', false)->values();
                break;

            case 'favorites':
                $breadcrumbs = [['id' => 0, 'name' => 'Favoritos', 'icon' => 'fas fa-star']];
                $favoriteItems = $this->settingRepository->getFavorites(auth()->id());
                $fileIds = collect($favoriteItems)->where('is_folder', false)->pluck('id');
                $folderIds = collect($favoriteItems)->where('is_folder', true)->pluck('id');
                $files = MediaFile::byUser()->whereIn('id', $fileIds)->get()->map(fn ($f) => (object) array_merge($f->toArray(), ['is_folder' => false, 'human_size' => $f->human_size]));
                $folders = MediaFolder::byUser()->whereIn('id', $folderIds)->get()->map(fn ($f) => (object) array_merge($f->toArray(), ['is_folder' => true]));
                break;

            case 'recent':
                $breadcrumbs = [['id' => 0, 'name' => 'Recientes', 'icon' => 'fas fa-clock']];
                $files = MediaFile::byUser()->where('created_at', '>=', now()->subHours(24))->orderByDesc('created_at')->get()->map(fn ($f) => (object) array_merge($f->toArray(), ['is_folder' => false, 'human_size' => $f->human_size]));
                $folders = collect();
                break;

            default: // all_media
                $breadcrumbs = $this->buildBreadcrumbs($folderId);
                $items = $this->fileRepository->getFilesByFolderId(
                    $folderId,
                    ['search' => $search ?: null],
                    true,
                    ['search' => $search ?: null]
                );
                $folders = $items->where('is_folder', true)->values();
                $files = $items->where('is_folder', false)->forPage($page, $perPage)->values();
                break;
        }

        return response()->json([
            'files' => $files,
            'folders' => $folders,
            'breadcrumbs' => $breadcrumbs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($files),
            ],
        ]);
    }

    public function getBreadcrumbs(Request $request): JsonResponse
    {
        $folderId = $request->integer('folder_id', 0) ?: null;

        return response()->json([
            'breadcrumbs' => $this->buildBreadcrumbs($folderId),
        ]);
    }

    private function buildBreadcrumbs(int|string|null $folderId): array
    {
        $root = [['id' => 0, 'name' => 'Todos los archivos', 'icon' => 'fas fa-folder-open']];

        if (! $folderId) {
            return $root;
        }

        $parents = $this->folderRepository->getBreadcrumbs($folderId);

        $current = MediaFolder::find($folderId);
        if ($current) {
            $parents[] = ['id' => $current->id, 'name' => $current->name];
        }

        return array_merge($root, $parents);
    }

    private function getActiveDisk(): string
    {
        return session('media_active_disk', config('media.default_disk', 'media'));
    }

    private function getAvailableDisks(): array
    {
        $disks = [];

        foreach (config('filesystems.disks', []) as $diskName => $diskConfig) {
            $disks[$diskName] = [
                'name' => $diskName,
                'driver' => $diskConfig['driver'] ?? 'unknown',
                'label' => ucfirst($diskName),
            ];
        }

        return $disks;
    }

    public function setActiveDisk(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $request->validate(['disk' => 'required|string']);

        $disk = $request->string('disk');

        if (! config("filesystems.disks.{$disk}")) {
            return response()->json(['success' => false, 'message' => 'El disco seleccionado no existe'], 404);
        }

        session(['media_active_disk' => $disk]);

        return response()->json(['success' => true, 'disk' => $disk]);
    }

    public function emptyTrash(): JsonResponse
    {
        $this->authorize('forceDelete', MediaFile::class);

        $this->fileRepository->emptyTrash();
        $this->folderRepository->emptyTrash();

        return response()->json(['success' => true, 'message' => 'Papelera vaciada exitosamente']);
    }
}
