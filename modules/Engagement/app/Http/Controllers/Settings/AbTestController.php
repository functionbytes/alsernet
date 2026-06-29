<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\AbTest;
use Modules\Engagement\Traits\SoftDeletesActions;
use Modules\Helpdesk\Models\Inbox;

class AbTestController extends Controller
{
    use RecordsAudit;
    use SoftDeletesActions;

    public function __construct()
    {
        $this->middleware('can:engagement.ab_tests.view')->only('page', 'index', 'show');
        $this->middleware('can:engagement.ab_tests.create')->only('store');
        $this->middleware('can:engagement.ab_tests.update')->only('update', 'start', 'pause');
        $this->middleware('can:engagement.ab_tests.delete')->only('destroy');
        $this->middleware('can:engagement.ab_tests.view')->only('trashed');
        $this->middleware('can:engagement.ab_tests.update')->only('restore');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.ab-tests', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $rows = AbTest::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->forInbox((int) $id))
            ->withCount('variants')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(AbTest $abTest): JsonResponse
    {
        $abTest->load('variants');
        $stats = $abTest->variants->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'weight' => $v->weight,
            'impressions' => $v->impressions,
            'conversions' => $v->conversions,
            'conversion_rate' => $v->conversionRate(),
        ]);

        return response()->json([
            'success' => true,
            'data' => array_merge($abTest->toArray(), ['variant_stats' => $stats]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'variants' => ['required', 'array', 'min:2', 'max:5'],
            'variants.*.name' => ['required', 'string', 'max:100'],
            'variants.*.config' => ['required', 'array'],
            'variants.*.weight' => ['integer', 'min:1', 'max:99'],
        ]);

        $test = AbTest::query()->create([
            'inbox_id' => $validated['inbox_id'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'status' => AbTest::STATUS_DRAFT,
        ]);

        foreach ($validated['variants'] as $v) {
            $test->variants()->create([
                'name' => $v['name'],
                'config' => $v['config'],
                'weight' => $v['weight'] ?? 50,
            ]);
        }

        $this->audit('created', 'ab_test', $test->id);

        return response()->json(['success' => true, 'data' => $test->load('variants')], 201);
    }

    public function update(Request $request, AbTest $abTest): JsonResponse
    {
        if ($abTest->status === AbTest::STATUS_RUNNING) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar un test en ejecución. Pausa primero.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'variants' => ['sometimes', 'array', 'min:2', 'max:5'],
            'variants.*.id' => ['integer'],
            'variants.*.name' => ['required', 'string', 'max:100'],
            'variants.*.config' => ['required', 'array'],
            'variants.*.weight' => ['integer', 'min:1', 'max:99'],
        ]);

        $abTest->update([
            'name' => $validated['name'] ?? $abTest->name,
            'description' => $validated['description'] ?? $abTest->description,
        ]);

        if (isset($validated['variants'])) {
            // Simple replace: delete old, create new
            $abTest->variants()->delete();
            foreach ($validated['variants'] as $v) {
                $abTest->variants()->create([
                    'name' => $v['name'],
                    'config' => $v['config'],
                    'weight' => $v['weight'] ?? 50,
                ]);
            }
        }

        $this->audit('updated', 'ab_test', $abTest->id);

        return response()->json(['success' => true, 'data' => $abTest->fresh()->load('variants')]);
    }

    public function start(AbTest $abTest): JsonResponse
    {
        if ($abTest->status === AbTest::STATUS_RUNNING) {
            return response()->json(['success' => false, 'message' => 'Ya está en ejecución.'], 422);
        }

        $abTest->update([
            'status' => AbTest::STATUS_RUNNING,
            'started_at' => now(),
            'ended_at' => null,
            'winner_variant_id' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function pause(AbTest $abTest): JsonResponse
    {
        if ($abTest->status !== AbTest::STATUS_RUNNING) {
            return response()->json(['success' => false, 'message' => 'No está en ejecución.'], 422);
        }

        $abTest->update([
            'status' => AbTest::STATUS_PAUSED,
            'ended_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(AbTest $abTest): JsonResponse
    {
        if ($abTest->status === AbTest::STATUS_RUNNING) {
            return response()->json(['success' => false, 'message' => 'Pausa el test antes de eliminarlo.'], 422);
        }

        $id = $abTest->id;
        $abTest->variants()->delete();
        $abTest->delete();
        $this->audit('deleted', 'ab_test', $id);

        return response()->json(['success' => true]);
    }

    protected function getModelClass(): string
    {
        return AbTest::class;
    }
}
