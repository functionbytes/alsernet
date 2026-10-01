<?php

namespace Modules\HelpdeskAiPrompts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Helpdesk\Models\Inbox;
use Modules\HelpdeskAiPrompts\Http\Requests\StoreAiActionRequest;
use Modules\HelpdeskAiPrompts\Http\Requests\TestAiActionRequest;
use Modules\HelpdeskAiPrompts\Http\Requests\UpdateAiActionRequest;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionFormMapper;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionPanel;
use Modules\HelpdeskAiPrompts\Services\Actions\BridgeAllowlist;
use Modules\HelpdeskAiPrompts\Support\VersionDiff;

class AiActionController extends Controller
{
    private const RUNS_SHOWN = 50;

    public function create(Request $request, ActionFormMapper $mapper): View
    {
        $type = $request->query('type') === AiAction::TYPE_HTTP ? AiAction::TYPE_HTTP : AiAction::TYPE_BRIDGE;

        return $this->form(new AiAction(['type' => $type]), $mapper);
    }

    public function edit(AiAction $aiAction, ActionFormMapper $mapper): View
    {
        return $this->form($aiAction, $mapper);
    }

    public function store(StoreAiActionRequest $request, ActionFormMapper $mapper): JsonResponse
    {
        $input = $request->validated();
        $action = AiAction::query()->create($mapper->attributes($input, $input['type']));

        return $this->saved(__('helpdeskaiprompts::ai-prompts.actions.created', ['name' => $action->name]), 201);
    }

    public function update(UpdateAiActionRequest $request, AiAction $aiAction, ActionFormMapper $mapper): JsonResponse
    {
        $input = $request->validated();
        $attributes = $aiAction->type === AiAction::TYPE_BUILTIN
            ? $mapper->builtinAttributes($input, $aiAction)
            : $mapper->attributes($input, $aiAction->type, $aiAction);

        $aiAction->update($attributes);

        return $this->saved(__('helpdeskaiprompts::ai-prompts.actions.updated', ['name' => $aiAction->name]));
    }

    public function destroy(AiAction $aiAction): RedirectResponse
    {
        abort_if($aiAction->type === AiAction::TYPE_BUILTIN, 403, __('helpdeskaiprompts::ai-prompts.actions.builtin_not_deletable'));

        $aiAction->delete();

        return redirect()->route('helpdesk-ai-prompts.index', ['tab' => 'acciones'])
            ->with('success', __('helpdeskaiprompts::ai-prompts.actions.deleted'));
    }

    public function toggleActive(AiAction $aiAction): JsonResponse
    {
        $aiAction->update(['is_active' => ! $aiAction->is_active, 'updated_by' => auth()->id()]);

        return response()->json(['is_active' => $aiAction->is_active]);
    }

    /**
     * Ejecuta la acción de verdad con el contexto simulado del formulario.
     * Devuelve solo lo que vería la IA: nunca secretos ni errores internos.
     */
    public function test(TestAiActionRequest $request, AiAction $aiAction, ActionExecutor $executor, ActionPanel $panel): JsonResponse
    {
        abort_if($aiAction->type === AiAction::TYPE_BUILTIN, 404);

        if (! $aiAction->is_active) {
            return response()->json(['message' => __('helpdeskaiprompts::ai-prompts.actions.test_inactive')], 422);
        }

        $verified = $request->boolean('verified');
        $args = $this->coerceArgs($panel->effectiveParams($aiAction, $verified), (array) $request->input('args', []));
        $context = [
            'verified' => $verified,
            'customer_email' => $verified ? $request->input('customer_email') : null,
            'customer_ps_id' => null,
            'trace_id' => 'panel-test-'.uniqid(),
            'channel' => ($aiAction->channels ?? [])[0] ?? 'web',
            'locale' => app()->getLocale(),
        ];

        $startedAt = hrtime(true);
        $result = $executor->run($aiAction->key, $args, $context, 'test');

        return response()->json([
            'ok' => $result['ok'],
            'status' => $result['status'],
            'content' => $result['content'],
            'latency_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ]);
    }

    public function history(AiAction $aiAction): View
    {
        $current = $aiAction->attributesToArray();
        $versions = $aiAction->versions()->get();

        return view('helpdeskaiprompts::history', [
            'subjectLabel' => $aiAction->name,
            'versions' => $versions,
            'currentVersion' => $aiAction->version,
            'backLabel' => __('helpdeskaiprompts::ai-prompts.tab_acciones'),
            'backRoute' => route('helpdesk-ai-prompts.index', ['tab' => 'acciones']),
            'restoreRoute' => fn (AiPromptVersion $version) => route('helpdesk-ai-prompts.actions.versions.restore', [$aiAction, $version]),
            'diffs' => $versions->mapWithKeys(
                fn (AiPromptVersion $version) => [$version->id => VersionDiff::changedFields((array) $version->snapshot, $current)]
            ),
            'runs' => AiActionRun::query()
                ->where('action_key', $aiAction->key)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::RUNS_SHOWN)
                ->get(),
        ]);
    }

    public function restoreVersion(AiAction $aiAction, AiPromptVersion $version): RedirectResponse
    {
        $back = redirect()->route('helpdesk-ai-prompts.actions.history', $aiAction);

        try {
            $aiAction->restoreVersion($version);
        } catch (\InvalidArgumentException) {
            abort(404);
        } catch (ValidationException $e) {
            // La versión ya no cumple las reglas actuales (lista blanca, hosts...).
            return $back->with('error', collect($e->errors())->flatten()->implode(' '));
        }

        return $back->with('success', __('helpdeskaiprompts::ai-prompts.version_restored'));
    }

    private function form(AiAction $action, ActionFormMapper $mapper): View
    {
        $allowlist = collect(BridgeAllowlist::actions())->mapWithKeys(
            fn (string $name): array => [$name => BridgeAllowlist::find($name)]
        );

        return view('helpdeskaiprompts::actions.form', [
            'action' => $action,
            'form' => $mapper->toForm($action),
            'allowlist' => $allowlist,
            'allowedHosts' => (array) config('ai-actions.http_allowed_hosts', []),
            'maxTimeout' => (int) config('ai-actions.max_timeout', 10),
            'defaultMaxChars' => (int) config('ai-actions.default_max_chars', 1500),
            'channels' => Inbox::CHANNEL_TYPES,
        ]);
    }

    private function saved(string $message, int $status = 200): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json([
            'message' => $message,
            'redirect' => route('helpdesk-ai-prompts.index', ['tab' => 'acciones']),
        ], $status);
    }

    /**
     * El formulario envía todo como texto; el ejecutor es estricto con los
     * booleanos. Solo se pasan los parámetros que la acción declara.
     *
     * @param  array<int, array<string, mixed>>  $params
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function coerceArgs(array $params, array $input): array
    {
        $args = [];

        foreach ($params as $param) {
            $value = $input[$param['name']] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $args[$param['name']] = $param['type'] === 'boolean'
                ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                : $value;
        }

        return $args;
    }
}
