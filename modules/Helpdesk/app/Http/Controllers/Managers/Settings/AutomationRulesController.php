<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Helpdesk\Models\AutomationRule;
use Modules\Helpdesk\Services\Automation\ConditionEvaluator;

class AutomationRulesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.automation-rules.view')->only(['index']);
        $this->middleware('can:helpdesk.automation-rules.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.automation-rules.update')->only(['edit', 'update', 'toggle']);
        $this->middleware('can:helpdesk.automation-rules.delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = AutomationRule::query();

        if ($request->filled('event_name')) {
            $query->where('trigger_event', $request->event_name);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->orderBy('order')->orderBy('id')->paginate(15);

        $statsRow = AutomationRule::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active,
            'inactive' => (int) $statsRow->inactive,
        ];

        return view('helpdesk::settings.automation-rules.index', compact('rules', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.automation-rules.create', [
            'events' => AutomationRule::EVENTS,
            'conditionFields' => AutomationRule::CONDITION_FIELDS,
            'conditionOperators' => AutomationRule::CONDITION_OPERATORS,
            'actionTypes' => AutomationRule::ACTION_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['conditions'] = $this->parseJson($request->input('conditions_json')) ?? [];
        $validated['actions'] = $this->parseJson($request->input('actions_json')) ?? [];

        if ($error = $this->invalidRegexError($validated['conditions'])) {
            return back()->withInput()->withErrors(['conditions_json' => $error]);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['user_id'] = auth()->id();
        $validated['trigger_event'] = $request->input('event_name');
        unset($validated['event_name']);

        AutomationRule::create($validated);

        return redirect()
            ->route('settings.helpdesk.automation-rules.index')
            ->with('success', 'Regla de automatizacion creada exitosamente.');
    }

    public function edit(AutomationRule $automationRule): View
    {
        return view('helpdesk::settings.automation-rules.edit', [
            'automationRule' => $automationRule,
            'events' => AutomationRule::EVENTS,
            'conditionFields' => AutomationRule::CONDITION_FIELDS,
            'conditionOperators' => AutomationRule::CONDITION_OPERATORS,
            'actionTypes' => AutomationRule::ACTION_TYPES,
        ]);
    }

    public function update(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $validated['conditions'] = $this->parseJson($request->input('conditions_json')) ?? [];
        $validated['actions'] = $this->parseJson($request->input('actions_json')) ?? [];

        if ($error = $this->invalidRegexError($validated['conditions'])) {
            return back()->withInput()->withErrors(['conditions_json' => $error]);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['trigger_event'] = $request->input('event_name');
        unset($validated['event_name']);

        $automationRule->update($validated);

        return redirect()
            ->route('settings.helpdesk.automation-rules.index')
            ->with('success', 'Regla de automatizacion actualizada exitosamente.');
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        $automationRule->delete();

        return redirect()
            ->route('settings.helpdesk.automation-rules.index')
            ->with('success', 'Regla de automatizacion eliminada exitosamente.');
    }

    public function toggle(AutomationRule $automationRule): JsonResponse
    {
        $automationRule->update(['is_active' => ! $automationRule->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $automationRule->is_active,
            'message' => $automationRule->is_active ? 'Regla activada.' : 'Regla desactivada.',
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'event_name' => ['required', 'string', Rule::in(array_keys(AutomationRule::EVENTS))],
            'order' => ['nullable', 'integer', 'min:0'],
            'conditions_json' => ['nullable', 'string'],
            'actions_json' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'event_name.required' => 'El evento de disparo es obligatorio.',
            'event_name.in' => 'El evento seleccionado no es valido.',
        ];
    }

    /**
     * Rechaza en el guardado los patrones regex inválidos o demasiado largos
     * (anti-ReDoS): la misma validación que ConditionEvaluator aplicará al
     * evaluar la condición contra cuerpos de mensaje.
     *
     * @param  array<string, mixed>  $conditions
     */
    private function invalidRegexError(array $conditions): ?string
    {
        $invalid = ConditionEvaluator::firstInvalidRegexPattern($conditions);

        if ($invalid === null) {
            return null;
        }

        return sprintf(
            'La expresión regular "%s" no es válida o supera los %d caracteres.',
            mb_substr($invalid, 0, 80),
            ConditionEvaluator::MAX_REGEX_PATTERN_LENGTH
        );
    }

    /**
     * @return array<int, mixed>|null
     */
    private function parseJson(?string $raw): ?array
    {
        if (blank($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
