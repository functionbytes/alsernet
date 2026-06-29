<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Social\Http\Requests\StoreHashtagGroupRequest;
use Modules\Social\Http\Requests\UpdateHashtagGroupRequest;
use Modules\Social\Models\HashtagGroup;

class HashtagGroupController extends Controller
{
    public function index()
    {
        $hashtagGroups = HashtagGroup::where('account_id', auth()->user()->account_id)
            ->orderBy('usage_count', 'desc')
            ->get();

        return view('social::hashtags.index', compact('hashtagGroups'));
    }

    public function create()
    {
        return view('social::hashtags.create');
    }

    public function store(StoreHashtagGroupRequest $request)
    {
        HashtagGroup::create($request->validated());

        return redirect()
            ->route('admin.social.hashtags.index')
            ->with('success', 'Grupo de hashtags creado exitosamente');
    }

    public function edit(HashtagGroup $hashtag)
    {
        $this->authorize('update', $hashtag);

        return view('social::hashtags.edit', compact('hashtag'));
    }

    public function update(UpdateHashtagGroupRequest $request, HashtagGroup $hashtag)
    {
        $hashtag->update($request->validated());

        return redirect()
            ->route('admin.social.hashtags.index')
            ->with('success', 'Grupo de hashtags actualizado exitosamente');
    }

    public function destroy(HashtagGroup $hashtag)
    {
        $this->authorize('delete', $hashtag);

        $hashtag->delete();

        return redirect()
            ->route('admin.social.hashtags.index')
            ->with('success', 'Grupo de hashtags eliminado exitosamente');
    }
}
