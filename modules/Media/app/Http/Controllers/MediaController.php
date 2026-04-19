<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Media\Http\Requests\BulkActionRequest;
use Modules\Media\Models\MediaFavorite;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Repositories\Interfaces\MediaFileInterface;
use Modules\Media\Repositories\Interfaces\MediaFolderInterface;
use Modules\Media\Repositories\Interfaces\MediaSettingInterface;
use Modules\Media\Services\MediaFileService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(
        protected MediaFileInterface $fileRepository,
        protected MediaFolderInterface $folderRepository,
        protected MediaSettingInterface $settingRepository,
        protected MediaFileService $fileService
    ) {}

    public function index(): View
    {
        return view('media::index', [
            'activeDisk' => $this->getActiveDisk(),
            'availableDisks' => $this->getAvailableDisks(),
            'pickerMode' => request()->boolean('picker'),
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
        $files = collect();
        $folders = collect();

        switch ($view) {
            case 'trash':
                $breadcrumbs = [['id' => 0, 'name' => 'Papelera', 'icon' => 'fas fa-trash']];
                $items = $this->fileRepository->getTrashed($folderId);
                $folders = $items->where('is_folder', true)->values();
                $files = $items->where('is_folder', false)->values();
                break;

            case 'favorites':
                $activeDisk = $this->getActiveDisk();
                $breadcrumbs = [['id' => 0, 'name' => 'Favoritos', 'icon' => 'fas fa-star']];
                $favoriteItems = $this->settingRepository->getFavorites(auth()->id());
                $fileIds = collect($favoriteItems)->where('is_folder', false)->pluck('id');
                $folderIds = collect($favoriteItems)->where('is_folder', true)->pluck('id');
                $files = MediaFile::byUser()->whereIn('id', $fileIds)
                    ->where('disk', $activeDisk)
                    ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->get()
                    ->map(fn ($f) => $this->formatFileForList($f));
                $folders = MediaFolder::byUser()->whereIn('id', $folderIds)
                    ->where('disk', $activeDisk)
                    ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->get()
                    ->map(fn ($f) => (object) array_merge($f->toArray(), ['is_folder' => true]));
                break;

            case 'recent':
                $activeDisk = $this->getActiveDisk();
                $breadcrumbs = [['id' => 0, 'name' => 'Recientes', 'icon' => 'fas fa-clock']];
                $files = MediaFile::byUser()
                    ->where('disk', $activeDisk)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($f) => $this->formatFileForList($f));
                $folders = collect();
                break;

            default: // all_media
                $activeDisk = $this->getActiveDisk();
                $breadcrumbs = $this->buildBreadcrumbs($folderId);
                $items = $this->fileRepository->getFilesByFolderId(
                    $folderId,
                    ['search' => $search ?: null, 'disk' => $activeDisk],
                    true,
                    ['search' => $search ?: null, 'disk' => $activeDisk]
                );
                $folders = $items->where('is_folder', true)->values();
                $allFiles = $items->where('is_folder', false);
                $totalFiles = $allFiles->count();
                $files = $allFiles->forPage($page, $perPage)->values();
                break;
        }

        // Add public_url to files that have a url
        $files = $files->map(function ($file) {
            $obj = is_object($file) ? $file : (object) $file;
            if (! empty($obj->url) && ! isset($obj->public_url)) {
                $obj->public_url = url('media/'.$obj->url);
            }

            return $obj;
        });

        $totalFiles = $totalFiles ?? $files->count();

        return response()->json([
            'files' => $files->values(),
            'folders' => $folders,
            'breadcrumbs' => $breadcrumbs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiles,
                'last_page' => max(1, (int) ceil($totalFiles / $perPage)),
            ],
        ]);
    }

    /**
     * Format a MediaFile model for list API response.
     */
    private function formatFileForList(MediaFile $file): object
    {
        return (object) array_merge($file->toArray(), [
            'is_folder' => false,
            'human_size' => $file->human_size,
            'public_url' => url('media/'.$file->url),
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
        $allowList = ['media', 'public', 's3'];
        $disks = [];

        foreach (config('filesystems.disks', []) as $diskName => $diskConfig) {
            if (! in_array($diskName, $allowList)) {
                continue;
            }

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

    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $type = $request->validated('type');
        $ids = $request->validated('ids');
        $userId = auth()->id();

        return DB::transaction(function () use ($action, $type, $ids, $userId, $request) {
            $model = $type === 'file' ? MediaFile::class : MediaFolder::class;
            $items = $model::withTrashed()->whereIn('id', $ids)->where('user_id', $userId)->get();

            foreach ($items as $item) {
                match ($action) {
                    'delete' => $item->delete(),
                    'restore' => $item->restore(),
                    'move' => $type === 'file'
                        ? $item->update(['folder_id' => $request->validated('folder_id')])
                        : $item->update(['parent_id' => $request->validated('folder_id')]),
                    'favorite' => MediaFavorite::firstOrCreate([
                        'user_id' => $userId,
                        'favoritable_id' => $item->id,
                        'favoritable_type' => $model,
                    ]),
                    'unfavorite' => MediaFavorite::where([
                        'user_id' => $userId,
                        'favoritable_id' => $item->id,
                        'favoritable_type' => $model,
                    ])->delete(),
                };
            }

            return response()->json([
                'success' => true,
                'message' => 'Acción aplicada a '.$items->count().' elementos',
                'count' => $items->count(),
            ]);
        });
    }

    public function getQuotaUsage(): JsonResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $userId = auth()->id();
        $used = $this->fileService->getUserUsage($userId);
        $total = $this->fileService->getUserQuota($userId);

        return response()->json([
            'used' => $used,
            'total' => $total,
            'enabled' => config('media.quota.enabled', false),
            'percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:image,video,audio,document,pdf,zip'],
            'min_size' => ['nullable', 'integer', 'min:0'],
            'max_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'min_width' => ['nullable', 'integer', 'min:1'],
            'min_height' => ['nullable', 'integer', 'min:1'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $results = $this->fileService->search(
            $request->validated(),
            auth()->id(),
            $request->integer('per_page', 20)
        );

        return response()->json($results);
    }

    public function emptyTrash(): JsonResponse
    {
        $this->authorize('forceDelete', MediaFile::class);

        $this->fileRepository->emptyTrash();
        $this->folderRepository->emptyTrash();

        return response()->json(['success' => true, 'message' => 'Papelera vaciada exitosamente']);
    }

    public function bulkDownload(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'type' => ['required', 'in:file,folder'],
        ]);

        $userId = auth()->id();
        $type = $request->input('type');

        $files = $type === 'file'
            ? MediaFile::query()->whereIn('id', $request->input('ids'))->where('user_id', $userId)->get()
            : MediaFile::query()->whereIn('folder_id', $request->input('ids'))->where('user_id', $userId)->get();

        $zipName = 'media_'.now()->format('Ymd_His').'.zip';

        return response()->stream(function () use ($files): void {
            $zipPath = tempnam(sys_get_temp_dir(), 'mdl_zip');
            $zip = new \ZipArchive;
            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($files as $file) {
                $disk = Storage::disk($file->disk);
                if ($disk->exists($file->url)) {
                    $zip->addFile($disk->path($file->url), $file->id.'_'.$file->name);
                }
            }

            $zip->close();
            readfile($zipPath);
            @unlink($zipPath);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
        ]);
    }

    public function duplicates(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MediaFile::class);

        $userId = auth()->id();
        $mode = $request->input('mode', 'exact');

        if ($mode === 'exact') {
            $hashes = MediaFile::query()
                ->select('file_hash')
                ->where('user_id', $userId)
                ->whereNotNull('file_hash')
                ->groupBy('file_hash')
                ->havingRaw('count(*) > 1')
                ->pluck('file_hash');

            $files = MediaFile::query()
                ->whereIn('file_hash', $hashes)
                ->where('user_id', $userId)
                ->orderBy('file_hash')
                ->orderBy('created_at')
                ->get();

            $grouped = $files->groupBy('file_hash')->map(fn ($g) => $g->values());

            return response()->json([
                'mode' => 'exact',
                'groups' => $grouped,
                'total_duplicates' => $files->count() - $grouped->count(),
                'wasted_bytes' => $grouped->sum(fn ($g) => ($g->count() - 1) * $g->first()->size),
            ]);
        }

        $files = MediaFile::query()
            ->where('user_id', $userId)
            ->whereNotNull('phash')
            ->get();

        $groups = [];
        $used = [];

        foreach ($files as $i => $fileA) {
            if (in_array($fileA->id, $used)) {
                continue;
            }

            $group = [$fileA];

            foreach ($files as $j => $fileB) {
                if ($i >= $j || in_array($fileB->id, $used)) {
                    continue;
                }

                if ($this->hammingDistanceHex($fileA->phash, $fileB->phash) <= 5) {
                    $group[] = $fileB;
                    $used[] = $fileB->id;
                }
            }

            if (count($group) > 1) {
                $used[] = $fileA->id;
                $groups[] = $group;
            }
        }

        return response()->json([
            'mode' => 'similar',
            'groups' => $groups,
            'total_groups' => count($groups),
        ]);
    }

    private function hammingDistanceHex(string $a, string $b): int
    {
        if (strlen($a) !== strlen($b)) {
            return 64;
        }

        $d = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $d += substr_count(decbin(hexdec($a[$i]) ^ hexdec($b[$i])), '1');
        }

        return $d;
    }
}
