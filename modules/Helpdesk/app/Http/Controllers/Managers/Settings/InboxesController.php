<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreInboxRequest;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Inbox;

class InboxesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only(['index', 'create', 'edit']);
        $this->middleware('can:helpdesk.settings.update')->only(['store', 'update', 'destroy', 'toggle']);
    }

    public function index(): View
    {
        $inboxes = Inbox::query()
            ->with(['defaultAssignee:id,firstname,lastname', 'defaultGroup:id,name'])
            ->withCount('conversations')
            ->orderBy('channel_type')
            ->orderBy('name')
            ->get();

        $channelLabels = Inbox::channelLabels();
        $channelIcons = Inbox::channelIcons();

        return view('helpdesk::settings.inboxes.index', compact(
            'inboxes',
            'channelLabels',
            'channelIcons',
        ));
    }

    public function create(Request $request): View
    {
        $channel = $request->input('channel', Inbox::CHANNEL_WHATSAPP);
        if (! in_array($channel, Inbox::CHANNEL_TYPES, true)) {
            $channel = Inbox::CHANNEL_WHATSAPP;
        }

        $inbox = new Inbox(['channel_type' => $channel, 'is_active' => true]);

        return $this->renderForm($inbox);
    }

    public function store(StoreInboxRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['greeting_enabled'] = $request->boolean('greeting_enabled');
        $data['working_hours_enabled'] = $request->boolean('working_hours_enabled');

        $inbox = Inbox::create($data);

        return redirect()
            ->route('settings.helpdesk.inboxes.edit', $inbox)
            ->with('success', 'Inbox creado correctamente.');
    }

    public function edit(Inbox $inbox): View
    {
        return $this->renderForm($inbox);
    }

    public function update(StoreInboxRequest $request, Inbox $inbox): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['greeting_enabled'] = $request->boolean('greeting_enabled');
        $data['working_hours_enabled'] = $request->boolean('working_hours_enabled');

        // Si el form no envió credentials (no se editaron), conservamos las existentes.
        // Si el form mandó credentials vacías o con valores, las actualizamos.
        if (! $request->has('credentials')) {
            unset($data['credentials']);
        }

        $inbox->update($data);

        return redirect()
            ->route('settings.helpdesk.inboxes.edit', $inbox)
            ->with('success', 'Inbox actualizado.');
    }

    public function destroy(Inbox $inbox): RedirectResponse
    {
        $inbox->delete();

        return redirect()
            ->route('settings.helpdesk.inboxes.index')
            ->with('success', 'Inbox eliminado.');
    }

    public function toggle(Inbox $inbox): RedirectResponse
    {
        $inbox->update(['is_active' => ! $inbox->is_active]);

        return back()->with('success', $inbox->is_active ? 'Inbox activado.' : 'Inbox desactivado.');
    }

    private function renderForm(Inbox $inbox): View
    {
        $agents = User::query()
            ->select(['id', 'firstname', 'lastname', 'email'])
            ->orderBy('firstname')
            ->limit(200)
            ->get();

        $groups = Group::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $credentialFields = Inbox::credentialFields($inbox->channel_type);
        $channelLabels = Inbox::channelLabels();
        $channelIcons = Inbox::channelIcons();

        return view('helpdesk::settings.inboxes.form', compact(
            'inbox',
            'agents',
            'groups',
            'credentialFields',
            'channelLabels',
            'channelIcons',
        ));
    }
}
