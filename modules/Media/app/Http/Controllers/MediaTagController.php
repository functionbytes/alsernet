<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Media\Models\MediaTag;

class MediaTagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = MediaTag::query()
            ->where('user_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color']);

        return response()->json(['success' => true, 'tags' => $tags]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $slug = Str::slug($data['name']);

        $tag = MediaTag::firstOrCreate(
            ['slug' => $slug, 'user_id' => auth()->id()],
            ['name' => $data['name'], 'color' => $data['color'] ?? null]
        );

        return response()->json(['success' => true, 'tag' => $tag], 201);
    }

    public function destroy(MediaTag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->json(['success' => true]);
    }

    public function attach(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tag_id' => ['required', 'integer', 'exists:media_tags,id'],
            'taggable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:file,folder'],
        ]);

        $tag = MediaTag::findOrFail($data['tag_id']);
        $this->authorize('update', $tag);

        $taggable = $data['type'] === 'file'
            ? MediaFile::findOrFail($data['taggable_id'])
            : MediaFolder::findOrFail($data['taggable_id']);

        $taggable->tags()->syncWithoutDetaching([$tag->id]);

        return response()->json(['success' => true]);
    }

    public function detach(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tag_id' => ['required', 'integer', 'exists:media_tags,id'],
            'taggable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:file,folder'],
        ]);

        $tag = MediaTag::findOrFail($data['tag_id']);
        $this->authorize('update', $tag);

        $taggable = $data['type'] === 'file'
            ? MediaFile::findOrFail($data['taggable_id'])
            : MediaFolder::findOrFail($data['taggable_id']);

        $taggable->tags()->detach($tag->id);

        return response()->json(['success' => true]);
    }
}
