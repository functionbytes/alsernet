<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\HelpdeskLivechat\Http\Requests\WidgetTriggerRequest;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Models\WidgetTrigger;

/**
 * Disparadores proactivos del chat web (live commerce, fase 5).
 */
class WidgetTriggersController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.triggers.manage');
    }

    public function index(Request $request): View
    {
        $triggers = WidgetTrigger::query()
            ->with('web')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('priority')
            ->orderBy('id')
            ->paginate(20);

        $stats = [
            'total' => WidgetTrigger::count(),
            'active' => WidgetTrigger::where('is_active', true)->count(),
            'message' => WidgetTrigger::where('action', 'message')->count(),
            'cart' => WidgetTrigger::where('conditions', 'like', '%"cart_%')->count(),
        ];

        return view('helpdesklivechat::settings.triggers.index', compact('triggers', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesklivechat::settings.triggers.form', ['trigger' => null, 'webs' => $this->webs()]);
    }

    public function store(WidgetTriggerRequest $request): RedirectResponse
    {
        WidgetTrigger::create($request->triggerData());

        return redirect()->route('settings.helpdesk-livechat.triggers.index')
            ->with('success', __('helpdesklivechat::triggers.created'));
    }

    public function edit(WidgetTrigger $trigger): View
    {
        return view('helpdesklivechat::settings.triggers.form', ['trigger' => $trigger, 'webs' => $this->webs()]);
    }

    public function update(WidgetTriggerRequest $request, WidgetTrigger $trigger): RedirectResponse
    {
        $trigger->update($request->triggerData());

        return redirect()->route('settings.helpdesk-livechat.triggers.index')
            ->with('success', __('helpdesklivechat::triggers.updated'));
    }

    public function destroy(WidgetTrigger $trigger): RedirectResponse
    {
        $trigger->delete();

        return redirect()->route('settings.helpdesk-livechat.triggers.index')
            ->with('success', __('helpdesklivechat::triggers.deleted'));
    }

    /**
     * @return Collection<int, Web>
     */
    private function webs()
    {
        return Web::query()->with('inbox')->orderBy('id')->get();
    }
}
