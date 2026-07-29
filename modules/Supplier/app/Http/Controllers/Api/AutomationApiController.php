<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Http\Requests\Api\RunWorkflowRequest;
use Modules\Supplier\Http\Resources\AutomationExecutionResource;
use Modules\Supplier\Http\Resources\AutomationWorkflowResource;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationWorkflow;
use Modules\Supplier\Services\AutomationOrchestrationService;

class AutomationApiController extends Controller
{
    public function __construct(private AutomationOrchestrationService $orchestration) {}

    public function workflows(Request $request): AnonymousResourceCollection
    {
        $workflows = AutomationWorkflow::query()
            ->when($request->supplier_id, fn ($q, $id) => $q->whereHas('executions', fn ($eq) => $eq->where('supplier_id', $id)))
            ->when($request->status === 'active', fn ($q) => $q->active())
            ->when($request->status === 'inactive', fn ($q) => $q->inactive())
            ->orderByPriority()
            ->paginate($request->integer('per_page', 15));

        return AutomationWorkflowResource::collection($workflows);
    }

    public function run(RunWorkflowRequest $request, string $uid): JsonResponse
    {
        $workflow = AutomationWorkflow::where('uid', $uid)->firstOrFail();

        if (! $workflow->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'El workflow no esta activo.',
            ], 422);
        }

        $execution = $this->orchestration->executeWorkflow($workflow, array_merge(
            $request->validated(),
            ['trigger_type' => 'api', 'triggered_by' => $request->user()->id]
        ));

        return response()->json([
            'success' => true,
            'execution' => new AutomationExecutionResource($execution),
        ], 201);
    }

    public function executions(Request $request): AnonymousResourceCollection
    {
        $limit = min($request->integer('limit', 100), 100);

        $executions = AutomationExecution::query()
            ->when($request->workflow_id, fn ($q, $id) => $q->forWorkflow($id))
            ->when($request->status, fn ($q, $status) => $q->withStatus($status))
            ->latest()
            ->limit($limit)
            ->get();

        return AutomationExecutionResource::collection($executions);
    }

    public function retry(int $id): JsonResponse
    {
        $execution = AutomationExecution::with('workflow')->findOrFail($id);

        if (! $execution->isFailed()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden reintentar ejecuciones fallidas.',
            ], 422);
        }

        $newExecution = $this->orchestration->executeWorkflow($execution->workflow, [
            'trigger_type' => 'retry',
            'triggered_by' => request()->user()?->id,
            'context' => ['retried_from' => $execution->id],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ejecucion reiniciada.',
            'execution' => new AutomationExecutionResource($newExecution),
        ], 201);
    }
}
