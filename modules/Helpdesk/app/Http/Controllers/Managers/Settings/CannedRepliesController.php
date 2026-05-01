<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\StoreCannedReplyRequest;
use Modules\Helpdesk\Http\Requests\UpdateCannedReplyRequest;
use Modules\Helpdesk\Models\CannedReply;

class CannedRepliesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.canned-replies.view')->only(['index']);
        $this->middleware('can:helpdesk.canned-replies.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.canned-replies.update')->only(['edit', 'update']);
        $this->middleware('can:helpdesk.canned-replies.delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = CannedReply::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('scope')) {
            $query->where('is_global', $request->scope === 'global');
        }

        $cannedReplies = $query->latest()->paginate(20);

        $statsRow = CannedReply::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_global = 1 THEN 1 ELSE 0 END) as global_count,
            SUM(CASE WHEN is_global = 0 OR is_global IS NULL THEN 1 ELSE 0 END) as personal_count
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'global' => (int) $statsRow->global_count,
            'personal' => (int) $statsRow->personal_count,
        ];

        $categories = CannedReply::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('helpdesk::settings.canned-replies.index', compact('cannedReplies', 'stats', 'categories'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.canned-replies.create');
    }

    public function store(StoreCannedReplyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_global'] = $request->boolean('is_global');
        $data['user_id'] = auth()->id();

        CannedReply::create($data);

        return redirect()
            ->route('settings.helpdesk.canned-replies.index')
            ->with('success', 'Respuesta predefinida creada exitosamente.');
    }

    public function edit(CannedReply $cannedReply): View
    {
        return view('helpdesk::settings.canned-replies.edit', compact('cannedReply'));
    }

    public function update(UpdateCannedReplyRequest $request, CannedReply $cannedReply): RedirectResponse
    {
        $data = $request->validated();
        $data['is_global'] = $request->boolean('is_global');

        $cannedReply->update($data);

        return redirect()
            ->route('settings.helpdesk.canned-replies.index')
            ->with('success', 'Respuesta predefinida actualizada exitosamente.');
    }

    public function destroy(CannedReply $cannedReply): RedirectResponse
    {
        $cannedReply->delete();

        return redirect()
            ->route('settings.helpdesk.canned-replies.index')
            ->with('success', 'Respuesta predefinida eliminada exitosamente.');
    }
}
