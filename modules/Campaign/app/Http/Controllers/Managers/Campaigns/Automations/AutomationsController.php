<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Campaign\Models\Automation\Automation;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Services\AutomationService;

/**
 * Slim AutomationsController.
 *
 * CRUD de automatizaciones + builder JSON. El "workflow" se guarda como
 * JSON en la columna `data` y el motor (Library/Automation/{Trigger,
 * Wait, Evaluate, Send, ...}) lo evalúa al ejecutarse.
 */
class AutomationsController extends Controller
{
    public function __construct(protected AutomationService $service) {}

    public function index(Request $request): View
    {
        $automations = Automation::query()
            ->when($request->query('q'), fn ($q, $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('campaign::manager.automations.index', compact('automations'));
    }

    public function create(): View
    {
        return view('campaign::manager.automations.create', [
            'mailLists' => CampaignMaillist::orderBy('name')->get(),
            'triggerTypes' => $this->triggerTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mail_list_id' => ['required', 'exists:campaign_maillists,id'],
            'trigger_type' => ['required', 'string', 'in:'.implode(',', array_keys($this->triggerTypes()))],
        ]);

        // Estructura mínima del workflow: trigger único + acción "send-email" placeholder.
        // El usuario podrá editarla en /edit con un JSON editor.
        $workflow = [
            'trigger' => [
                'type' => $data['trigger_type'],
                'options' => [],
            ],
            'children' => [],
        ];

        $automation = $this->service->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'mail_list_id' => $data['mail_list_id'],
            'time_zone' => config('app.timezone', 'UTC'),
            'status' => Automation::STATUS_INACTIVE,
            'data' => json_encode($workflow),
        ]);

        return redirect()
            ->route('manager.campaigns.automations.edit', $automation->uid)
            ->with('success', 'Automatización creada. Configura los pasos del workflow.');
    }

    public function show(string $uid): View
    {
        $automation = Automation::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.automations.show', [
            'automation' => $automation,
            'workflow' => json_decode((string) $automation->data, true) ?: [],
        ]);
    }

    public function edit(string $uid): View
    {
        $automation = Automation::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.automations.edit', [
            'automation' => $automation,
            'workflow' => json_decode((string) $automation->data, true) ?: [],
            'triggerTypes' => $this->triggerTypes(),
        ]);
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $automation = Automation::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'data' => ['nullable', 'json'], // workflow JSON serializado
        ]);

        $this->service->update($automation, $data);

        return back()->with('success', 'Automatización actualizada.');
    }

    public function destroy(string $uid): RedirectResponse
    {
        $automation = Automation::where('uid', $uid)->firstOrFail();
        $this->service->delete($automation);

        return redirect()
            ->route('manager.campaigns.automations.index')
            ->with('success', 'Automatización eliminada.');
    }

    public function enable(string $uid): RedirectResponse
    {
        $automation = Automation::where('uid', $uid)->firstOrFail();
        $this->service->enable($automation);

        return back()->with('success', 'Automatización activada.');
    }

    public function disable(string $uid): RedirectResponse
    {
        $automation = Automation::where('uid', $uid)->firstOrFail();
        $this->service->disable($automation);

        return back()->with('success', 'Automatización desactivada.');
    }

    /**
     * Tipos de trigger que soporta el motor (constantes de Automation).
     */
    protected function triggerTypes(): array
    {
        return [
            Automation::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER => 'Bienvenida a nuevo suscriptor',
            Automation::TRIGGER_TYPE_SAY_GOODBYE_TO_SUBSCRIBER => 'Adiós a suscriptor que se desuscribe',
            Automation::TRIGGER_TYPE_SAY_HAPPY_BIRTHDAY => 'Felicidades de cumpleaños',
            Automation::TRIGGER_SUBSCRIPTION_ANNIVERSARY_DATE => 'Aniversario de suscripción',
            Automation::TRIGGER_PARTICULAR_DATE => 'Fecha específica',
            Automation::TRIGGER_API => 'Disparado por API',
            Automation::TRIGGER_WEEKLY_RECURRING => 'Recurrente semanal',
            Automation::TRIGGER_MONTHLY_RECURRING => 'Recurrente mensual',
        ];
    }
}
