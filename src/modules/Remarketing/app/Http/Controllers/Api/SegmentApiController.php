<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Remarketing\Http\Requests\Api\StoreSegmentRequest;
use Modules\Remarketing\Http\Requests\Api\UpdateSegmentRequest;
use Modules\Remarketing\Http\Resources\CustomerResource;
use Modules\Remarketing\Http\Resources\SegmentResource;
use Modules\Remarketing\Jobs\RecalculateSegmentJob;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Services\SegmentService;

class SegmentApiController extends Controller
{
    public function __construct(
        protected SegmentService $segments,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Segment::class);

        $segments = Segment::query()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return SegmentResource::collection($segments);
    }

    public function store(StoreSegmentRequest $request): JsonResponse
    {
        $segment = Segment::create($request->validated());

        RecalculateSegmentJob::dispatch($segment);

        return (new SegmentResource($segment))->response()->setStatusCode(201);
    }

    public function show(Segment $segment): SegmentResource
    {
        $this->authorize('view', $segment);

        return new SegmentResource($segment);
    }

    public function update(UpdateSegmentRequest $request, Segment $segment): SegmentResource
    {
        $this->authorize('update', $segment);

        $segment->update($request->validated());

        RecalculateSegmentJob::dispatch($segment);

        return new SegmentResource($segment->fresh());
    }

    public function destroy(Segment $segment): JsonResponse
    {
        $this->authorize('delete', $segment);

        $segment->delete();

        return response()->json(null, 204);
    }

    public function preview(Segment $segment): AnonymousResourceCollection
    {
        $this->authorize('view', $segment);

        $customers = $this->segments->getMembers($segment)->limit(20)->get();

        return CustomerResource::collection($customers);
    }
}
