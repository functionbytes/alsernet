<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Remarketing\Http\Requests\Api\StoreSuppressionRequest;
use Modules\Remarketing\Http\Resources\SuppressionResource;
use Modules\Remarketing\Models\Suppression;

class SuppressionApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Suppression::class);

        $suppressions = Suppression::query()
            ->when($request->input('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->input('search'), fn ($q, $term) => $q->where('email', 'like', "%{$term}%"))
            ->latest()
            ->paginate($request->input('per_page', 25));

        return SuppressionResource::collection($suppressions);
    }

    public function store(StoreSuppressionRequest $request): JsonResponse
    {
        $suppression = Suppression::firstOrCreate(
            ['store_id' => $request->input('store_id'), 'email' => $request->input('email')],
            ['reason' => $request->input('reason', 'manual'), 'notes' => $request->input('notes')]
        );

        return (new SuppressionResource($suppression))->response()->setStatusCode(201);
    }

    public function destroy(Suppression $suppression): JsonResponse
    {
        $this->authorize('delete', $suppression);

        $suppression->delete();

        return response()->json(null, 204);
    }
}
