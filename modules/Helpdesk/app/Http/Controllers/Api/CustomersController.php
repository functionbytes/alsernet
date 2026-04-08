<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;

class CustomersController extends Controller
{
    /**
     * Search and list customers with pagination.
     *
     * GET /api/helpdesk/customers?q=&page=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|min:2',
        ]);

        $query = Customer::query()->select(['id', 'name', 'email', 'phone', 'created_at']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Get a single customer with ticket count.
     *
     * GET /api/helpdesk/customers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::withCount('tickets')->findOrFail($id);

        return response()->json($customer);
    }

    /**
     * Create a new customer.
     *
     * POST /api/helpdesk/customers
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:helpdesk_customers,email',
            'phone' => 'nullable|string|max:50',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }
}
