<?php

namespace Modules\Remarketing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Remarketing\Http\Resources\CustomerResource;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Event;
use Modules\Remarketing\Models\Store;

class CustomerApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->whereIn('store_id', $this->userStoreIds($request))
            ->when($request->input('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), fn ($q, $term) => $q->where('email', 'like', "%{$term}%"))
            ->latest()
            ->paginate($request->input('per_page', 15));

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    public function update(Request $request, Customer $customer): CustomerResource
    {
        $this->authorize('update', $customer);

        $customer->update($request->only([
            'first_name', 'last_name', 'phone', 'country', 'locale', 'tags', 'attributes',
        ]));

        return new CustomerResource($customer->fresh());
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->forceFill([
            'email' => 'deleted-'.$customer->id.'@gdpr.request',
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'birthday' => null,
        ])->save();

        $customer->delete();

        return response()->json(null, 204);
    }

    public function events(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $events = Event::query()
            ->where('customer_id', $customer->id)
            ->latest('occurred_at')
            ->paginate($request->input('per_page', 25));

        return response()->json($events);
    }

    protected function userStoreIds(Request $request): array
    {
        if ($request->user()->can('remarketing.manage')) {
            return Store::pluck('id')->all();
        }

        return Store::where('user_id', $request->user()->id)->pluck('id')->all();
    }
}
