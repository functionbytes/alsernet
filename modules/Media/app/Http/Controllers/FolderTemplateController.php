<?php

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Media\Models\FolderTemplate;
use Modules\Media\Models\MediaFolder;

class FolderTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = FolderTemplate::query()
            ->where('user_id', auth()->id())
            ->orWhere('is_shared', true)
            ->limit(200)
            ->get();

        return response()->json(['templates' => $templates]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MediaFolder::class);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'structure' => ['required', 'array'],
            'is_shared' => ['boolean'],
        ]);

        $template = FolderTemplate::create([
            'name' => $request->string('name'),
            'slug' => Str::slug($request->string('name')).'-'.uniqid(),
            'description' => $request->input('description'),
            'structure' => $request->input('structure'),
            'user_id' => auth()->id(),
            'is_shared' => $request->boolean('is_shared'),
        ]);

        return response()->json(['success' => true, 'template' => $template], 201);
    }

    public function apply(Request $request, FolderTemplate $template): JsonResponse
    {
        $this->authorize('create', MediaFolder::class);

        $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        $parentId = $request->integer('parent_id') ?: null;
        $created = [];
        $this->recursiveCreate($template->structure, $parentId, $created);

        return response()->json(['success' => true, 'created_folders' => $created]);
    }

    public function destroy(FolderTemplate $template): JsonResponse
    {
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $template->delete();

        return response()->json(['success' => true]);
    }

    private function recursiveCreate(array $nodes, ?int $parentId, array &$created): void
    {
        foreach ($nodes as $node) {
            $folder = MediaFolder::create([
                'name' => $node['name'],
                'slug' => MediaFolder::createSlug($node['name'], $parentId),
                'parent_id' => $parentId,
                'user_id' => auth()->id(),
                'disk' => session('media_active_disk', config('media.default_disk', 'media')),
                'color' => $node['color'] ?? '#3498db',
            ]);

            $created[] = $folder->id;

            if (! empty($node['children']) && is_array($node['children'])) {
                $this->recursiveCreate($node['children'], $folder->id, $created);
            }
        }
    }
}
