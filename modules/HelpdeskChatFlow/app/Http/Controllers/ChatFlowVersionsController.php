<?php

namespace Modules\HelpdeskChatFlow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Services\ChatFlowVersionDiff;

/**
 * Snapshots de un flow (se crean al publicar) y su restauración al borrador.
 */
class ChatFlowVersionsController extends Controller
{
    public function index(ChatFlow $chatFlow): View
    {
        $this->authorize('view', $chatFlow);

        $versions = $chatFlow->versions()->paginate(20);

        return view('chatflow::versions', compact('chatFlow', 'versions'));
    }

    /**
     * Compares a version snapshot against the current draft (default) or, when
     * `?against=` is given, against another version of the same flow.
     */
    public function diff(Request $request, ChatFlow $chatFlow, int $version, ChatFlowVersionDiff $differ): View
    {
        $this->authorize('view', $chatFlow);

        $base = $chatFlow->versions()->whereKey($version)->firstOrFail();

        $againstId = $request->integer('against') ?: null;
        $against = $againstId
            ? $chatFlow->versions()->whereKey($againstId)->firstOrFail()
            : null;

        $compareNodes = $against->nodes ?? $chatFlow->nodes ?? [];
        $diff = $differ->compare($base->nodes ?? [], $compareNodes);

        $otherVersions = $chatFlow->versions()->where('id', '!=', $base->id)->get(['id', 'name', 'created_at']);

        return view('chatflow::version-diff', compact('chatFlow', 'base', 'against', 'diff', 'otherVersions'));
    }

    public function restore(ChatFlow $chatFlow, int $version): RedirectResponse
    {
        $this->authorize('update', $chatFlow);

        $snapshot = $chatFlow->versions()->whereKey($version)->firstOrFail();

        $chatFlow->update(['nodes' => $snapshot->nodes]);

        return redirect()->route('chatflow.edit', $chatFlow)
            ->with('success', 'Versión restaurada. Revísala y vuelve a publicar.');
    }
}
