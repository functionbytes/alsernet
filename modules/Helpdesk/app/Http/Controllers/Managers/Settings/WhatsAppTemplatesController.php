<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Exceptions\WhatsAppTemplateException;
use Modules\Helpdesk\Http\Requests\Managers\Settings\StoreWhatsAppTemplateRequest;
use Modules\Helpdesk\Jobs\SyncWhatsAppTemplatesJob;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;
use Modules\Helpdesk\Services\WhatsAppTemplateCreationService;

class WhatsAppTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.whatsapp-templates.view')->only(['index']);
        $this->middleware('can:helpdesk.whatsapp-templates.manage')->only(['sync', 'create', 'store']);
    }

    public function index(Request $request): View
    {
        $query = WhatsAppTemplate::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('body_template', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $templates = $query->latest()->paginate(20);

        $stats = [
            'total' => WhatsAppTemplate::query()->count(),
            'approved' => WhatsAppTemplate::query()->where('status', 'approved')->count(),
            'pending' => WhatsAppTemplate::query()->where('status', 'pending')->count(),
            'rejected' => WhatsAppTemplate::query()->where('status', 'rejected')->count(),
        ];

        return view('helpdesk::settings.whatsapp-templates.index', compact('templates', 'stats'));
    }

    public function sync(): RedirectResponse
    {
        SyncWhatsAppTemplatesJob::dispatch();

        return redirect()
            ->route('settings.helpdesk.whatsapp-templates.index')
            ->with('success', 'Sincronizacion iniciada. Los templates se actualizaran en breve.');
    }

    public function create(): View
    {
        return view('helpdesk::settings.whatsapp-templates.create');
    }

    public function store(StoreWhatsAppTemplateRequest $request, WhatsAppTemplateCreationService $service): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $service->create(
                name: $validated['name'],
                language: $validated['language'],
                category: $validated['category'],
                body: $validated['body'],
                bodyExamples: $request->parsedBodyExamples(),
            );
        } catch (WhatsAppTemplateException $e) {
            return redirect()
                ->route('settings.helpdesk.whatsapp-templates.create')
                ->withInput()
                ->with('error', 'No se pudo crear la plantilla en Meta: '.$e->getMessage());
        }

        return redirect()
            ->route('settings.helpdesk.whatsapp-templates.index')
            ->with('success', 'Plantilla enviada a Meta para revisión. Aparecerá como "Aprobado" cuando Meta la revise (sincroniza para ver el estado actualizado).');
    }
}
