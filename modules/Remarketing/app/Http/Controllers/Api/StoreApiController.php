<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Remarketing\Http\Requests\Api\StoreStoreRequest;
use Modules\Remarketing\Http\Requests\Api\UpdateStoreRequest;
use Modules\Remarketing\Http\Resources\StoreResource;
use Modules\Remarketing\Jobs\SyncCatalogJob;
use Modules\Remarketing\Jobs\SyncCustomersJob;
use Modules\Remarketing\Jobs\SyncOrdersJob;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\DeliverabilityCheckerService;

class StoreApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Store::class);

        $stores = Store::query()
            ->when(! $request->user()->can('remarketing.manage'), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return StoreResource::collection($stores);
    }

    public function store(StoreStoreRequest $request): JsonResponse
    {
        $store = DB::transaction(function () use ($request) {
            return Store::create(array_merge(
                $request->validated(),
                [
                    'user_id' => $request->user()->id,
                    'webhook_token' => Str::random(64),
                    'status' => 'pending',
                ]
            ));
        });

        SyncCatalogJob::dispatch($store);
        SyncCustomersJob::dispatch($store);
        SyncOrdersJob::dispatch($store);

        return (new StoreResource($store))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Store $store): StoreResource
    {
        $this->authorize('view', $store);

        return new StoreResource($store);
    }

    public function update(UpdateStoreRequest $request, Store $store): StoreResource
    {
        $this->authorize('update', $store);

        $store->update($request->validated());

        return new StoreResource($store->fresh());
    }

    public function destroy(Store $store): JsonResponse
    {
        $this->authorize('delete', $store);

        $store->delete();

        return response()->json(null, 204);
    }

    public function sync(Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        SyncCatalogJob::dispatch($store);
        SyncCustomersJob::dispatch($store);
        SyncOrdersJob::dispatch($store);

        return response()->json(['ok' => true, 'message' => 'Sync dispatched']);
    }

    public function health(Store $store, DeliverabilityCheckerService $checker): JsonResponse
    {
        $this->authorize('view', $store);

        $domain = parse_url($store->domain, PHP_URL_HOST) ?: $store->domain;

        return response()->json([
            'store' => new StoreResource($store),
            'deliverability' => $checker->check($domain),
        ]);
    }
}
