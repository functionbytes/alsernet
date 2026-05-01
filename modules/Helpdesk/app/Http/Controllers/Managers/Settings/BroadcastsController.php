<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreBroadcastRequest;
use Modules\Helpdesk\Models\Campaigns\Broadcast;

class BroadcastsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.broadcasts.view')->only(['index', 'show']);
        $this->middleware('can:helpdesk.broadcasts.manage')->only(['create', 'store', 'destroy']);
    }

    public function index(Request $request): View
    {
        $query = Broadcast::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        $broadcasts = $query->latest()->paginate(15);

        $statsRow = Broadcast::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
            SUM(COALESCE(recipients_count, 0)) as recipients_total
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'draft' => (int) $statsRow->draft_count,
            'sent' => (int) $statsRow->sent_count,
            'failed' => (int) $statsRow->failed_count,
            'recipients_total' => (int) $statsRow->recipients_total,
        ];

        return view('helpdesk::settings.broadcasts.index', compact('broadcasts', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.broadcasts.create');
    }

    public function store(StoreBroadcastRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        Broadcast::create($data);

        return redirect()
            ->route('settings.helpdesk.broadcasts.index')
            ->with('success', 'Broadcast creado en borrador exitosamente.');
    }

    public function show(Broadcast $broadcast): View
    {
        $recipients = $broadcast->recipients()->latest()->paginate(20);

        return view('helpdesk::settings.broadcasts.show', compact('broadcast', 'recipients'));
    }

    public function destroy(Broadcast $broadcast): RedirectResponse
    {
        if (! $broadcast->isDraft()) {
            return redirect()
                ->route('settings.helpdesk.broadcasts.index')
                ->with('error', 'Solo se pueden eliminar broadcasts en estado borrador.');
        }

        $broadcast->delete();

        return redirect()
            ->route('settings.helpdesk.broadcasts.index')
            ->with('success', 'Broadcast eliminado exitosamente.');
    }
}
