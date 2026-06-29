<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Remarketing\Http\Requests\Api\StoreAutomationRequest;
use Modules\Remarketing\Http\Requests\Api\UpdateAutomationRequest;
use Modules\Remarketing\Http\Resources\AutomationResource;
use Modules\Remarketing\Models\Automation;

class AutomationApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Automation::class);

        $automations = Automation::query()
            ->with('steps')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return AutomationResource::collection($automations);
    }

    public function store(StoreAutomationRequest $request): JsonResponse
    {
        $automation = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $steps = $data['steps'] ?? [];
            unset($data['steps']);

            $automation = Automation::create($data);

            foreach ($steps as $step) {
                $automation->steps()->create($step);
            }

            return $automation;
        });

        return (new AutomationResource($automation->load('steps')))->response()->setStatusCode(201);
    }

    public function show(Automation $automation): AutomationResource
    {
        $this->authorize('view', $automation);

        return new AutomationResource($automation->load('steps'));
    }

    public function update(UpdateAutomationRequest $request, Automation $automation): AutomationResource
    {
        $this->authorize('update', $automation);

        DB::transaction(function () use ($request, $automation) {
            $data = $request->validated();
            $steps = $data['steps'] ?? null;
            unset($data['steps']);

            $automation->update($data);

            if (is_array($steps)) {
                $automation->steps()->delete();
                foreach ($steps as $step) {
                    $automation->steps()->create($step);
                }
            }
        });

        return new AutomationResource($automation->load('steps'));
    }

    public function destroy(Automation $automation): JsonResponse
    {
        $this->authorize('delete', $automation);

        $automation->delete();

        return response()->json(null, 204);
    }

    public function activate(Automation $automation): AutomationResource
    {
        $this->authorize('update', $automation);

        $automation->update(['status' => 'active']);

        return new AutomationResource($automation->fresh()->load('steps'));
    }

    public function pause(Automation $automation): AutomationResource
    {
        $this->authorize('update', $automation);

        $automation->update(['status' => 'paused']);

        return new AutomationResource($automation->fresh()->load('steps'));
    }
}
