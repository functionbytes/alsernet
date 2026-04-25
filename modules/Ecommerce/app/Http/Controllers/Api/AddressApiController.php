<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\CustomerAddressResource;
use Modules\Ecommerce\Models\CustomerAddress;

class AddressApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = CustomerAddress::query()
            ->where('customer_id', $request->user('sanctum')->id)
            ->latest()
            ->get();

        return response()->json(CustomerAddressResource::collection($addresses));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'type' => ['nullable', 'string', 'max:60'],
        ]);

        $customer = $request->user('sanctum');

        if (! empty($validated['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = $customer->addresses()->create($validated);

        return response()->json(new CustomerAddressResource($address), 201);
    }

    public function update(Request $request, CustomerAddress $address): JsonResponse
    {
        if ($address->customer_id !== $request->user('sanctum')->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'type' => ['nullable', 'string', 'max:60'],
        ]);

        if (! empty($validated['is_default'])) {
            $request->user('sanctum')->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json(new CustomerAddressResource($address));
    }

    public function destroy(Request $request, CustomerAddress $address): JsonResponse
    {
        if ($address->customer_id !== $request->user('sanctum')->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $address->delete();

        return response()->json(['message' => 'Direccion eliminada.']);
    }
}
