<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Http\Resources\CustomerResource;

class CustomerApiController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        return response()->json(new CustomerResource($request->user('sanctum')));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $request->user('sanctum');
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:customers,email,'.$customer->id],
            'phone' => ['sometimes', 'string', 'max:50'],
        ]);

        $customer->update($validated);

        return response()->json(new CustomerResource($customer));
    }
}
