<?php

namespace Modules\Helpdesk\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Api\IndexCustomerApiRequest;
use Modules\Helpdesk\Http\Requests\Api\StoreCustomerApiRequest;
use Modules\Helpdesk\Http\Requests\Api\UpdateCustomerApiRequest;
use Modules\Helpdesk\Http\Resources\CustomerResource;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\Customer;

/**
 * @group Customers
 *
 * Manage customer records.
 */
class CustomersApiController extends Controller
{
    /**
     * List customers
     *
     * Returns a paginated list of customers.
     *
     * @queryParam q string Search by name, email or phone. Example: maria
     * @queryParam per_page integer Results per page (default 15, max 100). Example: 20
     */
    public function index(IndexCustomerApiRequest $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ApiResponse::success(CustomerResource::collection($customers));
    }

    /**
     * Create customer
     *
     * @bodyParam name string required Customer display name. Example: Carlos Pérez
     * @bodyParam email string required Customer email. Example: carlos@example.com
     * @bodyParam phone string Customer phone number. Example: +34600123456
     */
    public function store(StoreCustomerApiRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return ApiResponse::created(new CustomerResource($customer), 'Cliente creado correctamente.');
    }

    /**
     * Get customer
     *
     * Returns a single customer with conversation count.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::withCount('conversations')->findOrFail($id);
        $this->authorize('view', $customer);

        return ApiResponse::success(new CustomerResource($customer));
    }

    /**
     * Update customer
     *
     * @bodyParam name string Customer display name. Example: Carlos Pérez
     * @bodyParam email string Customer email. Example: carlos@example.com
     * @bodyParam phone string Customer phone number. Example: +34600123456
     */
    public function update(UpdateCustomerApiRequest $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Cliente actualizado correctamente.');
    }
}
