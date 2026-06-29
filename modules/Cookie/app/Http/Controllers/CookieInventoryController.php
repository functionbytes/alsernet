<?php

namespace Modules\Cookie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Modules\Cookie\Http\Requests\StoreCookieInventoryRequest;
use Modules\Cookie\Http\Requests\UpdateCookieInventoryRequest;
use Modules\Cookie\Models\CookieInventory;

class CookieInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:cookie.inventory.view')->only('index');
        $this->middleware('can:cookie.inventory.create')->only('store');
        $this->middleware('can:cookie.inventory.update')->only('update');
        $this->middleware('can:cookie.inventory.delete')->only('destroy');
    }

    public function index(): View
    {
        $items = CookieInventory::query()->ordered()->paginate(20);

        return view('cookie::settings.inventory', compact('items'));
    }

    public function store(StoreCookieInventoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        $item = CookieInventory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cookie agregada al inventario.',
            'data' => $item,
        ], 201);
    }

    public function update(UpdateCookieInventoryRequest $request, CookieInventory $cookieInventory): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        $cookieInventory->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cookie actualizada.',
            'data' => $cookieInventory->fresh(),
        ]);
    }

    public function destroy(CookieInventory $cookieInventory): JsonResponse
    {
        $cookieInventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cookie eliminada del inventario.',
        ]);
    }
}
