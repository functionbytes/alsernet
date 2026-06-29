<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Api\IndexCustomerApiRequest;
use Modules\Helpdesk\Http\Requests\Api\StoreCustomerApiRequest;
use Modules\Helpdesk\Http\Resources\CustomerResource;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\Customer;

class CustomersController extends Controller
{
    public function index(IndexCustomerApiRequest $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($sub) => $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"));
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ApiResponse::success(CustomerResource::collection($customers));
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('helpdesk.customers.view');

        $customer = Customer::withCount('tickets')->findOrFail($id);

        return ApiResponse::success(new CustomerResource($customer));
    }

    public function store(StoreCustomerApiRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return ApiResponse::created(new CustomerResource($customer), 'Cliente creado correctamente.');
    }
}
