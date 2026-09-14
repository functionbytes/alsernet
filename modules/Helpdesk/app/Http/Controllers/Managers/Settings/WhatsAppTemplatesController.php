<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
        $this->middleware('can:helpdesk.whatsapp-templates.manage')->only(['sync', 'create', 'store', 'bulkAction']);
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

        // Casi todas las plantillas existen una vez por idioma, asi que sin
        // este filtro la lista se lee mal.
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        $templates = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => WhatsAppTemplate::query()->count(),
            'approved' => WhatsAppTemplate::query()->where('status', 'approved')->count(),
            'pending' => WhatsAppTemplate::query()->where('status', 'pending')->count(),
            'rejected' => WhatsAppTemplate::query()->where('status', 'rejected')->count(),
        ];

        $languages = WhatsAppTemplate::query()
            ->whereNotNull('language')
            ->distinct()
            ->orderBy('language')
            ->pluck('language');

        return view('helpdesk::settings.whatsapp-templates.index', compact('templates', 'stats', 'languages'));
    }

    public function sync(): RedirectResponse
    {
        SyncWhatsAppTemplatesJob::dispatch();

        return redirect()
            ->route('settings.helpdesk.whatsapp-templates.index')
            ->with('success', 'Sincronizacion iniciada. Los templates se actualizaran en breve.');
    }

    /**
     * Borrado en lote del espejo local.
     *
     * Las plantillas se sincronizan desde Meta: esto solo limpia la copia de
     * aqui, util para las que ya no existen alli. Una plantilla que siga viva
     * en Meta vuelve a aparecer en la siguiente sincronizacion, y eso es lo
     * correcto — no hay nada que borrar en Meta desde esta pantalla.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = WhatsAppTemplate::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => $count.' plantilla(s) eliminada(s) de la copia local.',
            'count' => $count,
        ]);
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
