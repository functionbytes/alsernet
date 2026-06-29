<?php

namespace Modules\HelpdeskLivechat\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskLivechat\Http\Requests\StorePreChatFormRequest;
use Modules\HelpdeskLivechat\Http\Requests\UpdatePreChatFormRequest;
use Modules\HelpdeskLivechat\Models\PreChatForm;

class PreChatFormsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.pre-chat.manage');
    }

    public function index(Request $request): View
    {
        $forms = PreChatForm::query()
            ->with('inbox')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => PreChatForm::count(),
            'active' => PreChatForm::where('is_active', true)->count(),
            'global' => PreChatForm::whereNull('inbox_id')->count(),
        ];

        return view('helpdesklivechat::settings.pre-chat-forms.index', compact('forms', 'stats'));
    }

    public function create(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get();

        return view('helpdesklivechat::settings.pre-chat-forms.form', compact('inboxes'));
    }

    public function store(StorePreChatFormRequest $request): RedirectResponse
    {
        $data = $this->prepareFormData($request);

        PreChatForm::create($data);

        return redirect()->route('settings.helpdesk-livechat.pre-chat-forms.index')
            ->with('success', 'Formulario pre-chat creado exitosamente.');
    }

    public function edit(PreChatForm $preChatForm): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get();

        return view('helpdesklivechat::settings.pre-chat-forms.form', [
            'form' => $preChatForm,
            'inboxes' => $inboxes,
        ]);
    }

    public function update(UpdatePreChatFormRequest $request, PreChatForm $preChatForm): RedirectResponse
    {
        $data = $this->prepareFormData($request);

        $preChatForm->update($data);

        return redirect()->route('settings.helpdesk-livechat.pre-chat-forms.index')
            ->with('success', 'Formulario pre-chat actualizado exitosamente.');
    }

    public function destroy(PreChatForm $preChatForm): RedirectResponse
    {
        $preChatForm->delete();

        return redirect()->route('settings.helpdesk-livechat.pre-chat-forms.index')
            ->with('success', 'Formulario pre-chat eliminado exitosamente.');
    }

    private function prepareFormData(StorePreChatFormRequest|UpdatePreChatFormRequest $request): array
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
