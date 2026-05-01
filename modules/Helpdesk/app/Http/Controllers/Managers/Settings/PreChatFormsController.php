<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Inbox;
use Modules\Helpdesk\Models\PreChatForm;

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

        return view('helpdesk::settings.pre-chat-forms.index', compact('forms', 'stats'));
    }

    public function create(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get();

        return view('helpdesk::settings.pre-chat-forms.form', compact('inboxes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateForm($request);

        PreChatForm::create($data);

        return redirect()->route('settings.helpdesk.pre-chat-forms.index')
            ->with('success', 'Formulario pre-chat creado exitosamente.');
    }

    public function edit(PreChatForm $preChatForm): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get();

        return view('helpdesk::settings.pre-chat-forms.form', [
            'form' => $preChatForm,
            'inboxes' => $inboxes,
        ]);
    }

    public function update(Request $request, PreChatForm $preChatForm): RedirectResponse
    {
        $data = $this->validateForm($request);

        $preChatForm->update($data);

        return redirect()->route('settings.helpdesk.pre-chat-forms.index')
            ->with('success', 'Formulario pre-chat actualizado exitosamente.');
    }

    public function destroy(PreChatForm $preChatForm): RedirectResponse
    {
        $preChatForm->delete();

        return redirect()->route('settings.helpdesk.pre-chat-forms.index')
            ->with('success', 'Formulario pre-chat eliminado exitosamente.');
    }

    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'inbox_id' => ['nullable', 'exists:helpdesk_inboxes,id'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.type' => ['required', 'in:text,email,phone,select,textarea,checkbox'],
            'fields.*.required' => ['boolean'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'fields.required' => 'Debes agregar al menos un campo.',
            'fields.min' => 'Debes agregar al menos un campo.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
