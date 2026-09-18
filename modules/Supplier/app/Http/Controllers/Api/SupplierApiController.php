<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Http\Requests\Api\ToggleSupplierRequest;
use Modules\Supplier\Http\Resources\SupplierResource;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $suppliers = Supplier::query()
            ->withCount(['products', 'sources'])
            ->with('categories')
            ->when($request->search, fn ($q, $search) => $q->search($search))
            ->when($request->has('available'), fn ($q) => $q->where('available', $request->boolean('available')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return SupplierResource::collection($suppliers);
    }

    public function show(string $uid): SupplierResource
    {
        $supplier = Supplier::with(['products', 'sources', 'categories'])
            ->where('uid', $uid)
            ->firstOrFail();

        return new SupplierResource($supplier);
    }

    public function toggle(ToggleSupplierRequest $request, string $uid): JsonResponse
    {
        $supplier = Supplier::where('uid', $uid)->firstOrFail();

        $supplier->update(['available' => $request->boolean('available')]);

        return response()->json([
            'success' => true,
            'message' => $supplier->available ? 'Proveedor activado.' : 'Proveedor desactivado.',
            'supplier' => new SupplierResource($supplier),
        ]);
    }
}
